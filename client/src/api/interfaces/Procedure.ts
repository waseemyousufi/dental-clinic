import type { ProcedureInventory } from "./InventoryStock";

export default interface ProcedureData {
  id: number;
  name: string;
  slug: string;
  category: string;
  base_price: number;
  dentist_commission: number;
  assistant_commission: number;
  is_active: boolean;
  inventory_requirements?: ProcedureInventory[]; // Bridge to Digital Twin
  created_at?: string;
  updated_at?: string;
}
