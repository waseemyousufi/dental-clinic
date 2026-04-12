import api from './api'
import type EmployeeData from './interfaces/Employee'
import type { EmployeeSalaryData } from './interfaces/Employee'
import fileManager from './utils/fileManager'

export default new (class Employee {
  constructor() { }

  getBranchEmployees(abbreviate: boolean = false) {
    if (abbreviate) return api.get('/employee?abr=true');
    return api.get('/employee')
  }

  postEmployee(data: EmployeeData) {
    return api.post('/employee', data)
  }

  updateEmployee(id: number, data: EmployeeData) {
    return api.put(`/employee/${id}`, data)
  }

  updateProfilePicture(id: number, file: File) {
    return fileManager.putFile(`/employee/${id}/profile-picture`, file as unknown as Blob)
  }

  payEmployee(data: EmployeeSalaryData) {
    return api.post('/employee-salary', data)
  }

  deleteEmployee(id: number) {
    return api.delete(`/employee/${id}`)
  }
})()
