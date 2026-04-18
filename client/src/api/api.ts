import { default as axios } from 'axios'

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
})

api.interceptors.request.use((req) => {
  const usr = JSON.parse(localStorage.getItem('user') || '{}')
  const role = usr?.user?.employee?.position
  const id = (new URLSearchParams(window.location.search)).get('branchId')
  const branchId = id || usr.user?.employee?.branchId

  // ✅ Safely add branchId to query params
  if (branchId) {
    req.params = { ...req.params, branchId }
  }

  // Handle baseURL based on endpoint
  if (req.url?.includes('chores')) {
    req.baseURL = 'http://127.0.0.1:8000/api'
  } else if (role) {
    req.baseURL = `http://127.0.0.1:8000/api/${role}`
  }

  // Add auth token
  if (usr.token) {
    req.headers.Authorization = `Bearer ${usr.token}`
  }

  return req
})

export default api
