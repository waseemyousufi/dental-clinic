export default interface EmployeeData {
  name: string,
  fName: string,
  lName: string,
  email: string,
  gender: string,
  hireDate: string,
  speciality: string,
  qualification: string,
  midLicenseNum: string,
  workStartTime: string,
  workEndTime: string,
  positionId: number,
  position: string,
  experience: EmployeeExperience
  id?: number,
}

export interface AbbrEmployees {
  id: number,
  name: string,
  position: string
}

interface EmployeeExperience {
  workplace: string,
  position: string,
  totalAmount: number,
}

export interface EmployeeSalaryData {
  salaryMonth: string,
  amount: number,
  bonus: number,
  totalAmount: number,
  remark: string,
  transactionId: number,
  employee?: string,
  employeeId?: number,
}
