import api from './api';
import type TreatmentPlanData from './interfaces/TreatmentPlan';
import { resolveBranchId } from './utils/branchParams';

export default new class TreatmentPlan {
  constructor(){}

  getBranchTreatmentPlans(patientId?: number, abbreviate: boolean = false, branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get(`/treatment-plan`, {
      params: {
        ...(patientId != null ? { patient_id: patientId } : {}),
        ...(abbreviate ? { abr: true } : {}),
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
        with: 'procedure,appointments', // Eager load relationships
      },
    })
  }

  postTreatmentPlan(data: TreatmentPlanData) {
    console.log('treatment plan data:', data)
    return api.post('/treatment-plan', data);
  }

  putTreatmentPlan(id: number,data: TreatmentPlanData) {
    return api.put(`/treatment-plan/${id}`, data)
  }

  updateTreatmentPlan(id: number, data: Partial<TreatmentPlanData>) {
    return api.put(`/treatment-plan/${id}`, data)
  }

  deleteTreatmentPlan(id:number) {
    return api.delete(`/treatment-plan/${id}`)
  }

  updateStatus(id: number, data: TreatmentPlanData) {
    return api.put(`treatment-plan/update-status/${id}`, data)
  }

  executeTreatmentPlan(id: number, data: any = {}) {
    return api.post(`/treatment-plan/${id}/execute`, data)
  }

  addAppointment(id: number, appointment_id: number) {
    return api.post(`/treatment-plan/${id}/appointments`, { appointment_id })
  }
}
