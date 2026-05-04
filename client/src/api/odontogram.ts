import api from './api'
import type { OdontogramData, SaveConditionPayload, ConditionLibrary } from './interfaces/Odontogram'

export default new (class Odontogram {
  constructor() { }

  /**
   * Fetches the complete odontogram state for a patient.
   * This returns all 52 teeth and their active conditions.
   */
  getPatientOdontogram(patientId: number) {
    return api.get<OdontogramData>(`/patients/${patientId}/odontogram`)
  }

  /**
   * Saves a new condition (finding/treatment) for a specific tooth.
   */

  saveToothCondition(patientId: number, data: SaveConditionPayload) {
    return api.post(`/patients/${patientId}/odontogram`, data)
  }

  /**
   * Fetches the library of available conditions (colors/labels)
   * to populate your "Palette" or "Legend" in the UI.
   */
  getConditionLibrary() {
    return api.get<ConditionLibrary[]>('/condition-library')
  }

  /**
   * Deletes or deactivates a condition if a mistake was made.
   */
  deleteToothCondition = (conditionId: string) => {
    // Matches your existing structure
    return api.delete(`/tooth-conditions/${conditionId}`);
  };
})()
