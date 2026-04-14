import type InventoryStockData from './InventoryStock'

export default interface ShelfData {
  id?: number
  shelfName: string
  shelfType: string
  accessPin?: string
  totalCapacityCm3: number
  categoryRestriction?: string
  usedCapacity?: number
  availableCapacity?: number
  inventoryStock?: InventoryStockData[]
}
