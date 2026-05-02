import api from './api'
import type EmployeeData from './interfaces/Employee'
import type { EmployeeSalaryData } from './interfaces/Employee'
import fileManager from './utils/fileManager'
import { resolveBranchId } from './utils/branchParams'

export default new (class Employee {
  constructor() { }

  getBranchEmployees(abbreviate: boolean = false, branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/employee', {
      params: {
        ...(abbreviate ? { abr: true } : {}),
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postEmployee(data: EmployeeData) {
    console.log(data)
    return api.post('/employee', data)
  }

  updateEmployee(id: number, data: EmployeeData) {
    return api.put(`/employee/${id}`, data)
  }

  updateProfilePicture(id: number, file: File) {
    return fileManager.putFile(`/employee/${id}/profile-picture`, file as unknown as Blob)
  }

  paySalary(id: number|string, data: EmployeeSalaryData) {
    return api.post(`/employee-salary/${id}`, data)
  }

  getBranchSalaries() {
    return api.get('/salaries')
  }

  async updateEmployeeProfilePicture(employeeId: number, file: File) {
    const formData = new FormData()
    formData.append('image', file)
    // Note: Some Laravel setups require a _method: 'PUT' if you are using a POST route to update
    // formData.append('_method', 'PUT')

    return api.post(`/employee/${employeeId}/update-profile-pic`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  }


  deleteEmployee(id: number) {
    return api.delete(`/employee/${id}`)
  }
})()
