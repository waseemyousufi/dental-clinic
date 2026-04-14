import type ShelfData from './Shelf'
import type ClinicMaterialData from './ClinicMaterial'
import type ClinicAssetData from './ClinicAsset'

export default interface InventoryStockData {
  id?: number
  stockableId: number
  stockableType: 'App\Models\ClinicMaterial' | 'App\Models\ClinicAsset'
  stockable?: ClinicMaterialData | ClinicAssetData
  shelfId?: number
  shelf?: ShelfData
  quantity: number
  expiryDate?: string
  batchNumber?: string
  status: 'placed' | 'pending'
  isExpired?: boolean
  createdAt?: string
  updatedAt?: string
}
