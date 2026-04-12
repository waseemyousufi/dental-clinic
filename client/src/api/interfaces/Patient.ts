export default interface PatientData {
  id: number,
  fName: string,
  lName: string,
  gender: string,
  bloodType: string,
  emgContact: string,
  registerationDate: string,
  phone: string,
}

export interface PatientAbbrData {
  id: number,
  name: string,
  phone: string
}

export interface PatientAllergyData {
  allergyType: string,
  severity: string,
  description: string,
  patientId: number,
}
