import { defineStore } from 'pinia'
import branchApi from '@/api/branch'
import type Branch from '@/api/interfaces/Branch'

const STORAGE_KEY = 'selectedBranchId'

export const useBranchStore = defineStore('branch', {
  state: () => ({
    branches: [] as Branch[],
    selectedBranchId: ((): number | null => {
      const raw = localStorage.getItem(STORAGE_KEY)
      if (!raw) return null
      const parsed = Number(raw)
      return Number.isFinite(parsed) ? parsed : null
    })(),
    loading: false,
  }),

  getters: {
    selectedBranch: (state) =>
      state.selectedBranchId == null
        ? null
        : state.branches.find((b) => b.id === state.selectedBranchId) ?? null,
  },

  actions: {
    setSelectedBranchId(id: number | string | null) {
      const parsed = typeof id === 'number' ? id : typeof id === 'string' ? Number(id) : null
      this.selectedBranchId = Number.isFinite(parsed) ? (parsed as number) : null
      if (this.selectedBranchId == null) localStorage.removeItem(STORAGE_KEY)
      else localStorage.setItem(STORAGE_KEY, String(this.selectedBranchId))
    },

    async fetchBranches() {
      if (this.loading) return
      this.loading = true
      try {
        const { data } = await branchApi.getBranches()
        this.branches = Array.isArray(data?.data) ? data.data : []

        // Keep selection stable, fall back to first branch if needed.
        const stillExists =
          this.selectedBranchId != null &&
          this.branches.some((b) => b.id === this.selectedBranchId)
        if (!stillExists) {
          this.setSelectedBranchId(this.branches[0]?.id ?? null)
        }
      } finally {
        this.loading = false
      }
    },
  },
})

