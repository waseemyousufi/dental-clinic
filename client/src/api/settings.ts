import api from './api'
import type { SettingsData } from './interfaces/Settings'
import { resolveBranchId } from './utils/branchParams'

export default new (class Settings {
  getSettings(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)

    return api.get<SettingsData>('/settings', {
      params: resolvedBranchId != null ? { branchId: resolvedBranchId } : {},
    })
  }

  updateSettings(branchId: number, data: Partial<SettingsData>) {
    return api.put<SettingsData>(`/settings/${branchId}`, data)
  }

  backupDatabase(type: 'full' | 'monthly') {
    return api.post('/settings/backup', { type })
  }
})()
