import type OrderItemData from './OrderItem'
import type SupplierData from './Supplier'

export default interface OrderData {
  id?: number
  supplierName: string
  supplierId: number
  supplier?: SupplierData
  date: string
  status: string
  notes?: string
  items?: OrderItemData[]
  itemsCount?: number
}
