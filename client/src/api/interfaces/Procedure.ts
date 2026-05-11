import type { ProcedureInventory } from "./InventoryStock";

export default interface ProcedureData {
  id?: number;
  name: string;
  slug: string;
  category: string;
  base_price: number;
  min_price?: number;
  appointments_needed?: number;
  dentist_commission: number;
  assistant_commission: number;
  is_active: boolean;
  inventory_requirements?: ProcedureInventory[];
  created_at?: string;
  updated_at?: string;
}
