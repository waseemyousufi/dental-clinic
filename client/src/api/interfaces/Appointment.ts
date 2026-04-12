export default interface AppointmentData {
  id: number,
  appointment_timestamp: string
  description: string
  status: string
  employeeId: number,
  employee?: string
  patient?: string,
  patientId: number,
}
