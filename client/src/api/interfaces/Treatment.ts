export default interface TreatmentData {
  id: number,
  treatmentType: string,
  diagnosis: string,
  treatmentDate: string,
  duration: string,
  cost: number,
  description: string,
  patientId: number,
  xrayId: number
}
