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

  // async backupDatabase(type: 'full' | 'monthly') {
  //   return api.post(
  //     `/settings/backup/${type}`,
  //     {},
  //     {
  //       responseType: 'blob',
  //     }
  //   )
  // }

  async triggerBackup() {
    return api.post(`/backup/${resolveBranchId()}`, {}, { responseType: 'blob' })
  }

  async restoreBackup(id: number, file: File) {
    const formData = new FormData()
    formData.append('backup_file', file)
    return api.post(`/restore-backup/${id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  }
})()
