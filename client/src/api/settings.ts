import api from './api'
import type { SettingsData } from './interfaces/Settings'
import { resolveBranchId } from './utils/branchParams'

type SettingsApiResponse = SettingsData | { data: SettingsData }

export default new (class Settings {
  getSettings(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)

    return api.get<SettingsApiResponse>('/settings', {
      params: resolvedBranchId != null ? { branchId: resolvedBranchId } : {},
    })
  }

  updateSettings(branchId: number, data: Partial<SettingsData>) {
    console.log('Updating settings with data:', data)
    const resolvedBranchId = resolveBranchId(branchId)
    const endpoint = resolvedBranchId != null ? `/settings/${resolvedBranchId}` : '/settings'

    return api.put<SettingsApiResponse>(endpoint, data, {
      params: resolvedBranchId != null ? { branchId: resolvedBranchId } : {},
    })
  }

  async backupDatabase(type: 'full' | 'monthly') {
    return api.post(
      `/settings/backup/${type}`,
      {},
      {
        responseType: 'blob',
      }
    )
  }
})()
