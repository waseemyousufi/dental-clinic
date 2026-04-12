import type EmployeeData from './Employee'

export default interface UserData {
  id: number,
  name: string,
  password: string,
  email: string,
  employee?: EmployeeData,
  token?: string,
}
