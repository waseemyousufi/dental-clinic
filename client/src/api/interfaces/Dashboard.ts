export type Tone = 'good' | 'warn' | 'bad' | 'up' | 'down' | 'neutral'

export interface DashboardBranchOption {
  id: number
  name: string
}

export interface DashboardKpiItem {
  key: string
  label: string
  value: number | string
  formatted: string
  trend?: number | null
  trend_label: string
  tone: Tone | string
  help: string
}

export interface DashboardChartDataset {
  label: string
  data: number[]
}

export interface DashboardChart {
  labels: string[]
  datasets: DashboardChartDataset[]
}

export interface DashboardAlert {
  tone?: 'good' | 'warn' | 'bad' | string
  title: string
  message: string
  meta?: { action?: string }
}

export interface DashboardRecentTreatmentRow {
  id: number
  patient_name: string
  branch_name: string
  treatment_type: string
  diagnosis: string
  date: string
  status: string
  amount: number
  range_fit: string
  balance: number
}

export interface DashboardRecentTransactionRow {
  id: number
  branch_name: string
  transaction_type: string
  amount: number
  date: string
  description: string
}

export default interface DashboardData {
  meta?: {
    generated_at?: string
    branch_id?: number | null
    branch_name?: string
    period_days?: number
    period_start?: string
    period_end?: string
  }
  filters?: {
    branches?: DashboardBranchOption[]
    selected_branch_id?: number | null
    selected_period_days?: number
  }
  kpis?: DashboardKpiItem[]
  charts?: Record<string, DashboardChart>
  alerts?: DashboardAlert[]
  recent?: {
    treatments?: DashboardRecentTreatmentRow[]
    transactions?: DashboardRecentTransactionRow[]
  }
}
