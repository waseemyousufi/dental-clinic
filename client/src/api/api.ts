import { default as axios } from 'axios'

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
})

api.interceptors.request.use((req) => {
  const usr = JSON.parse(localStorage.getItem('user') || '{}')
  const bId = (new URLSearchParams(window.location.search)).get('branchId')
  const branchId = bId || usr.user?.employee?.branchId

  // ✅ Safely add branchId to query params
  if (branchId) {
    req.params = { ...req.params, branchId }
  }

  // Add auth token
  if (usr.token) {
    req.headers.Authorization = `Bearer ${usr.token}`
  }

  return req
})

export default api
