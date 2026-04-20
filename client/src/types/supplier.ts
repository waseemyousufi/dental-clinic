export interface Product {
  id: string;
  name: string;
  sku: string;
  unit: 'piece' | 'box' | 'pack' | 'ml' | 'g';
  stock: number;
  minStock: number;
}

export interface Supplier {
  id: string;
  name: string;
  contactPerson: string;
  phone: string; // E.164 format: +1234567890
  email?: string;
  address?: string;
  businessId?: string; // Business registration/tax ID
  notes?: string;
  itemIds: number[]; // IDs of items this supplier provides (from items table)
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
  branchId: number;
  
}

// In PurchaseOrder interface:
export interface PurchaseOrder {
  id: string;
  supplierId: string;          
  _supplierName?: string;
  items: PurchaseOrderItem[];
  totalItems: number;
  createdAt: string;
  sentAt?: string;
  updatedAt?: string;
  status: 'draft' | 'pending' | 'received' | 'cancelled';
}

export interface PurchaseOrder {
  id: string;
  supplierId: string;
  items: PurchaseOrderItem[];
  totalItems: number;
  createdAt: string;
  sentAt?: string; // When the order was sent via WhatsApp
  status: 'draft' | 'sent' | 'confirmed' | 'delivered' | 'cancelled';
}
