export default interface ItemData {
  id: number
  name: string
  category: string
  materials?: string[]
  description?: string
  trackStock: boolean
  requiresBatch: boolean
  requiresExpiry: boolean
  isConsumable: boolean
  totalQuantityInStock?: number
  activePrice?: {
    id: number
    price: number
    discountPercentage?: number
    currencyExchangeRate?: number
    finalPrice?: number
    isActive: boolean
    effectiveFrom: string
  }
  suppliers?: { id: number; name: string }[]
}
