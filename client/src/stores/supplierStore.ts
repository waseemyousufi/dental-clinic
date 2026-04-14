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
    purchaseOrders.value.filter(o => o.status === 'sent')
  );

  const getSupplierById = (id: string) =>
    suppliers.value.find(s => s.id === id);

  const getProductsBySupplier = (supplierId: string) => {
    const supplier = getSupplierById(supplierId);
    if (!supplier) return [];
    return products.value.filter(p => supplier.productIds.includes(p.id));
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
    productIds: (api.items ?? []).map(i => String(i.id)),
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
    itemIds: ui.itemIds,
  });

  const mapApiOrderToUi = (api: OrderData): PurchaseOrder => ({
    id: String(api.id ?? Date.now()),
    supplierId: api.supplierName,
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
  });

  const mapUiOrderToApi = (supplierName: string, items: PurchaseOrderItem[]): OrderData => ({
    supplierName,
    date: new Date().toISOString().split('T')[0],
    status: 'draft',
    notes: '',
    items: items.map(item => ({
      itemId: Number(item.productId),
      quantity: item.quantity,
      unitPrice: 0,
      totalPrice: 0,
    })),
  });

  // Actions - API backed
  const loadInitialData = async () => {
    loading.value = true;
    try {
      // Load suppliers with their items
      const { data: suppliersData } = await supplierApi.getSuppliers();
      suppliers.value = (suppliersData.data ?? []).map(mapApiSupplierToUi);

      // Load items as products
      const { data: itemsData } = await itemApi.getItems();
      const items = itemsData.data ?? [];
      products.value = items.map((item: ItemData) => ({
        id: String(item.id),
        name: item.name,
        sku: `ITEM-${item.id}`,
        unit: item.isConsumable ? 'piece' : 'piece',
        stock: item.totalQuantityInStock ?? 0,
        minStock: item.requiresExpiry ? 10 : 5,
      }));

      // Load orders
      const { data: ordersData } = await orderApi.getOrders();
      purchaseOrders.value = (ordersData.data ?? []).map(mapApiOrderToUi);
    } catch (err) {
      console.error('Failed to load supplier data:', err);
    } finally {
      loading.value = false;
    }
  };

  const addSupplier = async (supplier: Omit<Supplier, 'id' | 'createdAt' | 'updatedAt'> & { itemIds?: number[] }) => {
    const apiData = mapUiSupplierToApi({ ...supplier, contactPersonName: supplier.contactPerson, organizationName: supplier.name, itemIds: supplier.itemIds });
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

  const updateSupplier = async (id: string, updates: Partial<Supplier> & { itemIds?: number[] }) => {
    const existing = suppliers.value.find(s => s.id === id);
    if (!existing) return false;

    const apiData = mapUiSupplierToApi({ ...existing, ...updates, contactPersonName: updates.contactPerson ?? existing.contactPerson, organizationName: updates.name ?? existing.name, itemIds: updates.itemIds });
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
      await updateSupplier(id, { isActive: !supplier.isActive });
    }
  };

  const createPurchaseOrder = async (supplierId: string, items: PurchaseOrderItem[]) => {
    const supplier = getSupplierById(supplierId);
    if (!supplier) throw new Error('Supplier not found');

    const apiData = mapUiOrderToApi(supplier.name, items);
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
    const apiData: OrderData = {
      supplierName: order.supplierId,
      date: order.createdAt,
      status: 'sent',
      items: order.items.map(item => ({
        itemId: Number(item.productId),
        quantity: item.quantity,
        unitPrice: 0,
        totalPrice: 0,
      })),
    };

    try {
      await orderApi.updateOrder(Number(orderId), apiData);
      purchaseOrders.value[idx].status = 'sent';
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
    const apiData: OrderData = {
      supplierName: order.supplierId,
      date: order.createdAt,
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

  const confirmDelivery = async (orderId: string) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === orderId);
    if (idx === -1) return false;

    const order = purchaseOrders.value[idx];
    const apiData: OrderData = {
      supplierName: order.supplierId,
      date: order.createdAt,
      status: 'delivered',
    };

    try {
      await orderApi.updateOrder(Number(orderId), apiData);
      purchaseOrders.value[idx].status = 'delivered';
      return true;
    } catch (err) {
      console.error('Failed to confirm delivery:', err);
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

  const deleteOrder = async (orderId: string) => {
    const idx = purchaseOrders.value.findIndex(o => o.id === orderId);
    if (idx > -1) {
      try {
        await orderApi.deleteOrder(Number(orderId));
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
