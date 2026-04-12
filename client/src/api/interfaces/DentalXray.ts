export default interface DentalXrayData {
  xray_type: string
  xray_timestamp: string
  tooth_part: string
  side: string
  image_path: string
  diagnosis_notes: string
  payment_status: string
  results_summery: string
  patient_id: number
  requestedByEmployee_id: number
  takenByEmployee_id: number
}
