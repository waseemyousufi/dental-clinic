import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Supplier, Product, PurchaseOrder } from '@/types/supplier';

export const useSupplierStore = defineStore('supplier', () => {
  const suppliers = ref<Supplier[]>([]);
  const products = ref<Product[]>([]);
  const purchaseOrders = ref<PurchaseOrder[]>([]);

  // Computed
  const activeSuppliers = computed(() => suppliers.value.filter(s => s.isActive));

  const pendingOrders = computed(() =>
    purchaseOrders.value.filter(o => o.status === 'sent')
  );

  const getSupplierById = (id: string) =>
    suppliers.value.find(s => s.id === id);

  const getProductsBySupplier = (supplierId: string) => {
    const supplier = getSupplierById(supplierId);
    if (!supplier) return [];
    return products.value.filter(p => supplier.productIds.includes(p.id));
  };

  // Actions
  const addSupplier = (supplier: Omit<Supplier, 'id' | 'createdAt' | 'updatedAt'>) => {
    const newSupplier: Supplier = {
      ...supplier,
      id: crypto.randomUUID(),
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    };
    suppliers.value.push(newSupplier);
    return newSupplier;
  };

  const updateSupplier = (id: string, updates: Partial<Supplier>) => {
    const idx = suppliers.value.findIndex(s => s.id === id);
    if (idx === -1) return false;
    suppliers.value[idx] = {
      ...suppliers.value[idx],
      ...updates,
      updatedAt: new Date().toISOString(),
    };
    return true;
  };

  const toggleSupplierStatus = (id: string) => {
    const supplier = getSupplierById(id);
    if (supplier) {
      updateSupplier(id, { isActive: !supplier.isActive });
    }
  };

  const createPurchaseOrder = (supplierId: string, items: PurchaseOrderItem[]) => {
    const order: PurchaseOrder = {
      id: crypto.randomUUID(),
      supplierId,
      items,
      totalItems: items.reduce((sum, i) => sum + i.quantity, 0),
      createdAt: new Date().toISOString(),
      status: 'draft',
    };
    purchaseOrders.value.unshift(order);
    return order;
  };

  const markOrderSent = (orderId: string) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === orderId);
    if (idx === -1) return false;
    purchaseOrders.value[idx].status = 'sent';
    purchaseOrders.value[idx].sentAt = new Date().toISOString();
    return true;
  };

  const cancelOrder = (orderId: string) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === orderId);
    if (idx === -1) return null;
    const order = { ...purchaseOrders.value[idx] };
    order.status = 'cancelled';
    purchaseOrders.value[idx] = order;
    return order;
  };

  const confirmDelivery = (orderId: string) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === orderId);
    if (idx === -1) return false;
    purchaseOrders.value[idx].status = 'delivered';
    return true;
  };

  const deleteOrder = (orderId: string) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === orderId);
    if (idx > -1) {
      purchaseOrders.value.splice(idx, 1);
      return true;
    }
    return false;
  };

  // Mock data loader (replace with API calls when backend is ready)
  const loadInitialData = () => {
    // Sample products
    products.value = [
      { id: 'p1', name: 'Composite Resin A2', sku: 'CR-A2-001', unit: 'piece', stock: 24, minStock: 10 },
      { id: 'p2', name: 'Disposable Gloves (Box)', sku: 'GLV-LAT-M', unit: 'box', stock: 15, minStock: 5 },
      { id: 'p3', name: 'Anesthetic Carpules', sku: 'ANC-LID-2%', unit: 'pack', stock: 8, minStock: 20 },
      { id: 'p4', name: 'Sterilization Pouches', sku: 'STP-MED-100', unit: 'pack', stock: 3, minStock: 10 },
      { id: 'p5', name: 'Dental Mirror', sku: 'DM-STD-001', unit: 'piece', stock: 12, minStock: 5 },
      { id: 'p6', name: 'Explorer Probe', sku: 'EP-STD-001', unit: 'piece', stock: 8, minStock: 5 },
      { id: 'p7', name: 'Cotton Rolls (100pk)', sku: 'CR-100-001', unit: 'pack', stock: 20, minStock: 10 },
      { id: 'p8', name: 'Bib Clips', sku: 'BC-STD-001', unit: 'piece', stock: 30, minStock: 15 },
    ];

    // Sample suppliers (if empty)
    if (suppliers.value.length === 0) {
      suppliers.value = [
        {
          id: 's1',
          name: 'DentalSupply Co.',
          contactPerson: 'John Smith',
          phone: '+1234567890',
          email: 'orders@dentalsupply.com',
          address: '123 Main St, Suite 100, New York, NY 10001',
          businessId: 'DS-123456',
          notes: 'Net 30 payment terms. Free shipping over $500.',
          productIds: ['p1', 'p2', 'p3', 'p7'],
          isActive: true,
          createdAt: '2024-01-15T10:00:00Z',
          updatedAt: '2024-01-15T10:00:00Z',
        },
        {
          id: 's2',
          name: 'MedEquip Distributors',
          contactPerson: 'Sarah Johnson',
          phone: '+1987654321',
          email: 'sales@medequip.com',
          address: '456 Oak Ave, Los Angeles, CA 90001',
          businessId: 'ME-789012',
          notes: 'COD. 10% discount on orders over $1000.',
          productIds: ['p4', 'p5', 'p6', 'p8'],
          isActive: true,
          createdAt: '2024-02-20T14:30:00Z',
          updatedAt: '2024-02-20T14:30:00Z',
        },
        {
          id: 's3',
          name: 'Global Dental Imports',
          contactPerson: 'Mike Chen',
          phone: '+1555123456',
          email: 'info@globaldental.com',
          businessId: 'GD-345678',
          productIds: ['p1', 'p5', 'p6'],
          isActive: false,
          createdAt: '2024-03-10T09:00:00Z',
          updatedAt: '2024-03-10T09:00:00Z',
        },
      ];
    }

    // Sample pending orders (if empty)
    if (purchaseOrders.value.length === 0) {
      purchaseOrders.value = [
        {
          id: 'po1',
          supplierId: 's1',
          items: [
            { productId: 'p1', productName: 'Composite Resin A2', quantity: 20, unit: 'piece', notes: 'Urgent' },
            { productId: 'p2', productName: 'Disposable Gloves (Box)', quantity: 30, unit: 'box' },
          ],
          totalItems: 50,
          createdAt: '2025-04-10T09:00:00Z',
          sentAt: '2025-04-10T09:15:00Z',
          status: 'sent',
        },
        {
          id: 'po2',
          supplierId: 's2',
          items: [
            { productId: 'p4', productName: 'Sterilization Pouches', quantity: 50, unit: 'pack', notes: 'Restock' },
          ],
          totalItems: 50,
          createdAt: '2025-04-11T14:00:00Z',
          sentAt: '2025-04-11T14:05:00Z',
          status: 'sent',
        },
        {
          id: 'po3',
          supplierId: 's1',
          items: [
            { productId: 'p3', productName: 'Anesthetic Carpules', quantity: 100, unit: 'pack' },
            { productId: 'p7', productName: 'Cotton Rolls (100pk)', quantity: 25, unit: 'pack' },
          ],
          totalItems: 125,
          createdAt: '2025-04-08T11:00:00Z',
          sentAt: '2025-04-08T11:10:00Z',
          status: 'delivered',
        },
      ];
    }
  };

  return {
    suppliers,
    products,
    purchaseOrders,
    pendingOrders,
    activeSuppliers,
    getSupplierById,
    getProductsBySupplier,
    addSupplier,
    updateSupplier,
    toggleSupplierStatus,
    createPurchaseOrder,
    markOrderSent,
    cancelOrder,
    confirmDelivery,
    deleteOrder,
    loadInitialData,
  };
});
