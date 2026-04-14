import type ProductPriceData from './ProductPrice'
import type InventoryStockData from './InventoryStock'

export default interface ClinicMaterialData {
  id?: number
  name: string
  materialName: string
  description?: string
  category?: string
  width?: number
  height?: number
  depth?: number
  isSterile?: boolean
  volume?: number
  quantity: number
  amount: number
  totalAmount: number
  expenseDate: string
  price?: number
  totalQuantityInStock?: number
  activePrice?: ProductPriceData
  prices?: ProductPriceData[]
  inventoryStock?: InventoryStockData[]
  branches?: { id: number; name: string }[]
  accountTransactions?: { id: number; amount: number }[]
}
