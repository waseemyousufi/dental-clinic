import type ProductPriceData from './ProductPrice'
import type InventoryStockData from './InventoryStock'
import type EmployeeData from './Employee'

export default interface ClinicAssetData {
  id?: number
  name: string
  assetName: string
  description?: string
  category: string
  width?: number
  height?: number
  depth?: number
  isSterile?: boolean
  volume?: number
  amount: number
  price: number
  totalAmount: number
  dateOfPurchase: string
  status: string
  purchasedByEmployeeId?: number
  branchId?: number
  totalQuantityInStock?: number
  purchasedByEmployee?: EmployeeData
  branch?: { id: number; name: string }
  activePrice?: ProductPriceData
  prices?: ProductPriceData[]
  inventoryStock?: InventoryStockData[]
}
