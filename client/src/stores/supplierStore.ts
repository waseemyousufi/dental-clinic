import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Supplier, Product, PurchaseOrder, PurchaseOrderItem } from '@/types/supplier';
import supplierApi from '@/api/supplier';
import orderApi from '@/api/order';
import itemApi from '@/api/item';
import type SupplierData from '@/api/interfaces/Supplier';
import type OrderData from '@/api/interfaces/Order';
import type OrderItemData from '@/api/interfaces/OrderItem';
import type ItemData from '@/api/interfaces/Item';

export const useSupplierStore = defineStore('supplier', () => {
  const suppliers = ref<Supplier[]>([]);
  const products = ref<Product[]>([]);
  const purchaseOrders = ref<PurchaseOrder[]>([]);
  const loading = ref(false);

  // Computed
  const activeSuppliers = computed(() => suppliers.value.filter(s => s.isActive));
  const pendingOrders = computed(() =>
    purchaseOrders.value.filter(o => o.status === 'pending')
  );

  // ✅ Fixed: Accept string | number, search by ID or name
  const getSupplierById = (id: string | number) => {
    const idStr = String(id);
    return suppliers.value.find(s => 
      s.id === idStr || 
      Number(s.id) === Number(id) ||
      s.name.toLowerCase() === idStr.toLowerCase()
    );
  };

  const getProductsBySupplier = (supplierId: string) => {
    const supplier = getSupplierById(supplierId);
    if (!supplier) return [];
    return products.value.filter(p => supplier.itemIds.includes(Number(p.id)));
  };

  // --- Mapping helpers ---
  const mapApiSupplierToUi = (api: SupplierData & { id?: number; items?: ItemData[] }): Supplier => ({
    id: String(api.id ?? Date.now()),
    name: api.organizationName,
    contactPerson: api.contactPersonName,
    phone: api.phone,
    email: api.email,
    businessId: api.businessId,
    notes: '',
    itemIds: (api.items ?? []).map(i => Number(i.id)),
    isActive: api.status === 'active',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  });

  const mapUiSupplierToApi = (ui: Partial<Supplier> & { contactPersonName?: string; organizationName?: string; itemIds?: number[] }): SupplierData & { itemIds?: number[] } => ({
    contactPersonName: ui.contactPersonName ?? ui.contactPerson ?? '',
    organizationName: ui.organizationName ?? ui.name ?? '',
    phone: ui.phone ?? '',
    email: ui.email,
    status: ui.isActive !== false ? 'active' : 'inactive',
    businessId: ui.businessId,
    itemIds: ui.itemIds ?? [],
  });

  // ✅ Fixed: Store both supplierId (numeric) and supplierName for flexibility
  const mapApiOrderToUi = (api: OrderData): PurchaseOrder => {
    // Try to resolve numeric supplier ID from name
    const supplier = suppliers.value.find(s => 
      s.name.toLowerCase() === (api.supplierName || '').toLowerCase()
    );
    
    return {
      id: String(api.id ?? Date.now()),
      supplierId: supplier ? supplier.id : (api.supplierName || String(Date.now())),
      _supplierName: api.supplierName, // Internal field for API calls
      items: (api.items ?? []).map((item: OrderItemData) => ({
        productId: String(item.itemId),
        productName: (item.item as ItemData | undefined)?.name ?? `Item #${item.itemId}`,
        quantity: item.quantity,
        unit: 'piece',
        notes: '',
      })),
      totalItems: (api.items ?? []).reduce((sum: number, i: OrderItemData) => sum + i.quantity, 0),
      createdAt: api.date,
      status: api.status as PurchaseOrder['status'],
    };
  };

  const mapUiOrderToApi = (supplierName: string, items: PurchaseOrderItem[]): OrderData => ({
    supplierName,
    date: new Date().toISOString().split('T')[0], // ✅ YYYY-MM-DD format
    status: 'draft',
    notes: '',
    items: items.map(item => ({
      itemId: Number(item.productId),
      quantity: item.quantity,
      unitPrice: 0,
      totalPrice: 0,
    })),
  });

  // ✅ Helper: Format date to YYYY-MM-DD
  const formatDateForApi = (date: string | Date): string => {
    return new Date(date).toISOString().split('T')[0];
  };

  // Actions - API backed
  const loadInitialData = async () => {
    loading.value = true;
    try {
      const { data: suppliersData } = await supplierApi.getBranchSuppliers();
      suppliers.value = (suppliersData.data ?? []).map(mapApiSupplierToUi);
      
      const { data: itemsData } = await itemApi.getItems();
      products.value = (itemsData.data ?? []).map((item: ItemData) => ({
        id: String(item.id),
        name: item.name,
        sku: `ITEM-${item.id}`,
        unit: item.isConsumable ? 'piece' : 'piece',
        stock: item.totalQuantityInStock ?? 0,
        minStock: item.requiresExpiry ? 10 : 5,
      }));

      const { data: ordersData } = await orderApi.getBranchOrders();
      purchaseOrders.value = (ordersData.data ?? []).map(mapApiOrderToUi);
    } catch (err) {
      console.error('Failed to load supplier data:', err);
    } finally {
      loading.value = false;
    }
  };

  const addSupplier = async (supplier: Omit<Supplier, 'id' | 'createdAt' | 'updatedAt'>) => {
    const apiData = mapUiSupplierToApi({ 
      ...supplier, 
      contactPersonName: supplier.contactPerson, 
      organizationName: supplier.name, 
      itemIds: supplier.itemIds 
    });
    try {
      const { data } = await supplierApi.postSupplier(apiData);
      const newSupplier = mapApiSupplierToUi(data.data);
      suppliers.value.push(newSupplier);
      return newSupplier;
    } catch (err) {
      console.error('Failed to create supplier:', err);
      throw err;
    }
  };

  const updateSupplier = async (id: string, updates: Partial<Supplier>) => {
    const existing = suppliers.value.find(s => s.id === id);
    if (!existing) return false;

    const apiData = mapUiSupplierToApi({ 
      ...existing, 
      ...updates, 
      contactPersonName: updates.contactPerson ?? existing.contactPerson, 
      organizationName: updates.name ?? existing.name, 
      itemIds: updates.itemIds ?? existing.itemIds 
    });
    try {
      const { data } = await supplierApi.updateSupplier(Number(id), apiData);
      const idx = suppliers.value.findIndex(s => s.id === id);
      if (idx > -1) {
        suppliers.value[idx] = mapApiSupplierToUi(data.data);
      }
      return true;
    } catch (err) {
      console.error('Failed to update supplier:', err);
      return false;
    }
  };

  const toggleSupplierStatus = async (id: string) => {
    const supplier = getSupplierById(id);
    if (supplier) {
      await updateSupplier(supplier.id, { isActive: !supplier.isActive });
    }
  };

  const createPurchaseOrder = async (supplierId: string, items: PurchaseOrderItem[]) => {
    const supplier = getSupplierById(supplierId);
    if (!supplier) throw new Error('Supplier not found');

    const apiData: OrderData = {
      supplierName: supplier.name, // ✅ Use name, not numeric ID
      supplierId: supplier.id,
      date: formatDateForApi(new Date()),
      status: 'draft',
      items: items.map(item => ({
        itemId: Number(item.productId),
        quantity: item.quantity,
        unitPrice: 0,
        totalPrice: 0,
      })),
    };
    
    try {
      const { data } = await orderApi.postOrder(apiData);
      const newOrder = mapApiOrderToUi(data.data);
      purchaseOrders.value.unshift(newOrder);
      return newOrder;
    } catch (err) {
      console.error('Failed to create order:', err);
      throw err;
    }
  };

  const markOrderSent = async (orderId: string) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === orderId);
    if (idx === -1) return false;

    const order = purchaseOrders.value[idx];
    const supplier = getSupplierById(order.supplierId);
    
    const apiData: OrderData = {
      supplierName: supplier?.name || order._supplierName || order.supplierId, // ✅ Use name
      date: formatDateForApi(order.createdAt), // ✅ YYYY-MM-DD
      status: 'pending',
      items: order.items.map(item => ({
        itemId: Number(item.productId),
        quantity: item.quantity,
        unitPrice: 0,
        totalPrice: 0,
      })),
    };
    
    try {
      await orderApi.updateOrder(Number(orderId), apiData);
      purchaseOrders.value[idx].status = 'pending';
      purchaseOrders.value[idx].sentAt = new Date().toISOString();
      return true;
    } catch (err) {
      console.error('Failed to mark order sent:', err);
      return false;
    }
  };

  const cancelOrder = async (orderId: string) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === orderId);
    if (idx === -1) return null;

    const order = purchaseOrders.value[idx];
    const supplier = getSupplierById(order.supplierId);
    
    const apiData: Partial<OrderData> = {
      supplierName: supplier?.name || order._supplierName || order.supplierId, // ✅ Use name
      date: formatDateForApi(order.createdAt),
      status: 'cancelled',
    };

    try {
      await orderApi.updateOrder(Number(orderId), apiData);
      purchaseOrders.value[idx].status = 'cancelled';
      return purchaseOrders.value[idx];
    } catch (err) {
      console.error('Failed to cancel order:', err);
      return null;
    }
  };

  // ✅ Fixed confirmDelivery - main source of 422 errors
  const confirmDelivery = async (orderId: string) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === orderId);
    if (idx === -1) return false;

    const order = purchaseOrders.value[idx];
    const supplier = getSupplierById(order.supplierId);
    
    const apiData: OrderData = {
      supplierName: supplier?.name || order._supplierName || order.supplierId, // ✅ Critical: use name
      date: formatDateForApi(order.createdAt), // ✅ YYYY-MM-DD format
      supplierId: order.supplierId,
      status: 'received',
      items: order.items.map(item => ({
        itemId: Number(item.productId),
        quantity: item.quantity,
        unitPrice: 0,
        totalPrice: 0,
      })),
    };

    try {
      console.log('Sending order update:', apiData);
      await orderApi.updateOrder(Number(orderId), apiData);
      purchaseOrders.value[idx].status = 'received';
      purchaseOrders.value[idx].updatedAt = new Date().toISOString();
      return true;
    } catch (err: any) {
      console.error('Failed to confirm delivery:', err);
      if (err.response?.data) {
        console.error('API validation errors:', JSON.stringify(err.response.data, null, 2));
      }
      return false;
    }
  };

  const updateOrderStatus = async (orderId: string, newStatus: PurchaseOrder['status']) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === orderId);
    if (idx === -1) return false;

    const order = purchaseOrders.value[idx];
    const supplier = getSupplierById(order.supplierId);
    
    const apiData: OrderData = {
      supplierName: supplier?.name || order._supplierName || order.supplierId, // ✅ Use name
      date: formatDateForApi(order.createdAt),
      status: newStatus,
      items: order.items.map(item => ({
        itemId: Number(item.productId),
        quantity: item.quantity,
        unitPrice: 0,
        totalPrice: 0,
      })),
    };

    try {
      await orderApi.updateOrder(Number(orderId), apiData);
      purchaseOrders.value[idx].status = newStatus;
      return true;
    } catch (err) {
      console.error('Failed to update order status:', err);
      return false;
    }
  };

  const deleteSupplier = async (id: string) => {
    const idx = suppliers.value.findIndex(s => s.id === id);
    if (idx > -1) {
      try {
        await supplierApi.deleteSupplier(Number(id));
        suppliers.value.splice(idx, 1);
        return true;
      } catch (err) {
        console.error('Failed to delete supplier:', err);
        return false;
      }
    }
    return false;
  };

  const deleteOrder = async (id: string) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === id);
    if (idx > -1) {
      try {
        await orderApi.deleteOrder(Number(id));
        purchaseOrders.value.splice(idx, 1);
        return true;
      } catch (err) {
        console.error('Failed to delete order:', err);
        return false;
      }
    }
    return false;
  };

  return {
    suppliers,
    products,
    purchaseOrders,
    loading,
    pendingOrders,
    activeSuppliers,
    getSupplierById,
    getProductsBySupplier,
    addSupplier,
    updateSupplier,
    toggleSupplierStatus,
    deleteSupplier,
    createPurchaseOrder,
    markOrderSent,
    cancelOrder,
    confirmDelivery,
    deleteOrder,
    loadInitialData,
  };
});