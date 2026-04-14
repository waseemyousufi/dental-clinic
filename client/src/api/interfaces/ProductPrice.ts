export default interface ProductPriceData {
  id?: number
  pricableId: number
  pricableType: string
  price: number
  discountPercentage?: number
  currencyExchangeRate?: number
  finalPrice?: number
  effectiveFrom: string
  isActive: boolean
  createdAt?: string
  updatedAt?: string
}
