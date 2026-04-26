export interface ConditionLibrary {
  id: number;
  label: string;
  slug: string;
  category: 'finding' | 'procedure' | 'restoration';
  ui_color: string;
  svg_path?: string;
}

export interface ToothCondition {
  id: string; // UUID
  patient_id: number;
  tooth_id: number;
  condition_id: number;
  surfaces: string[] | null; // e.g., ['M', 'O', 'D']
  drawing_data: any | null;   // For canvas coordinates
  notes: string | null;
  is_active: boolean;
  condition_library?: ConditionLibrary; // Loaded via eager loading
}

export interface Tooth {
  id: number;
  fdi_code: number;
  universal_code: string;
  type: 'permanent' | 'primary';
  quadrant: number;
  default_position: number;
  active_conditions: ToothCondition[]; // The current state of this specific tooth
}

export interface OdontogramData {
  patient_id: number;
  teeth: Tooth[];
}

export interface SaveConditionPayload {
  tooth_id: number;
  condition_id: number;
  surfaces?: string[];
  drawing_data?: any;
  notes?: string;
}
