import api from './api'

interface ReportFilters {
  branchId?: number | null
  days?: number
  startDate?: string | null
  endDate?: string | null
}

export const fetchReports = async (filters: ReportFilters) => {
  const response = await api.get('/reports', {
    params: {
      ...(filters.branchId != null ? { branchId: filters.branchId } : {}),
      ...(filters.days != null ? { days: filters.days } : {}),
      ...(filters.startDate ? { startDate: filters.startDate } : {}),
      ...(filters.endDate ? { endDate: filters.endDate } : {}),
    },
  })

  return response.data
}
