import type ProcedureData from "./Procedure";

export default interface TreatmentPlanData {
  id: number;
  patient_id: number;
  appointment_id: number;
  procedure_id: number;
  total_estimated_cost: number;
  status: 'proposed' | 'accepted' | 'partially_accepted' | 'rejected' | 'completed';
  procedure?: ProcedureData; // Eager loaded for UI labels
  created_at?: string;
  updated_at?: string;
}
