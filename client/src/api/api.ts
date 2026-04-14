import { default as axios } from 'axios'

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
})

api.interceptors.request.use((req) => {
  // Read user data fresh from localStorage on every request
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  const role = user?.user?.employee?.position?.position_title

  // If URL is for "chores" (shared endpoints), ensure no role prefix in baseURL
  if (req.url?.includes('chores')) {
    req.baseURL = 'http://127.0.0.1:8000/api'
  } else if (role) {
    // Otherwise use role-based prefix
    req.baseURL = `http://127.0.0.1:8000/api/${role}`
  }

  if (user.token) {
    req.headers.Authorization = `Bearer ${user.token}`
  }
  return req
})

export default api
