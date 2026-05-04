import api from './api';
import type DashboardData from './interfaces/Dashboard';

export default new class Dashboard {
  constructor() {}

  getBranchDashboard() {
    return api.get('/dashboard') as DashboardData;
  }
}
