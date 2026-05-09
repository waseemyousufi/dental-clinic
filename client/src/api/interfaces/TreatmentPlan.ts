import type ProcedureData from "./Procedure";
import type PatientData from "./Patient";

export default interface TreatmentPlanData {
  id?: number;
  patient_id: number;
  appointment_id?: number;
  procedure_id: number;
  total_estimated_cost: number;
  total_amount_paid?: number | null;
  duration?: number | null;
  start_date?: string;
  branch_id?: number;
  status: 'proposed' | 'accepted' | 'partially_accepted' | 'rejected' | 'completed';
  procedure?: ProcedureData; // Eager loaded for UI labels
  patient?: PatientData;
  appointments?: Array<{ id: number; treatment_plan_id?: number | null }>;
  created_at?: string;
  updated_at?: string;
}
