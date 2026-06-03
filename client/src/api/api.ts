import { default as axios } from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_BACKEND_URL,
})

api.interceptors.request.use((req) => {
  const usr = JSON.parse(localStorage.getItem('user') || '{}')
  const bId = (new URLSearchParams(window.location.search)).get('branchId')
  const branchId = bId || usr.user?.employee?.branchId

  const token = localStorage.getItem('token')
  if (branchId) {
    req.params = { ...req.params, branchId }
  }

  // Add auth token
  if (usr.token) {
    req.headers.Authorization = `Bearer ${usr.token}`
  } else if (token) {
    req.headers.Authorization = `Bearer ${token}`
  }

  return req
})

export default api
