import type ClinicMaterialData from './ClinicMaterial'

export default interface OrderItemData {
  id?: number
  orderId?: number
  itemId: number
  item?: ClinicMaterialData
  quantity: number
  unitPrice: number
  totalPrice: number
}
