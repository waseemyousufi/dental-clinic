import { default as axios } from 'axios'

const user = JSON.parse(localStorage.getItem('user') || '{}')
const role = user?.user?.employee?.position?.position_title
let baseURL = 'http://127.0.0.1:8000/api'
if (role) {
  baseURL = `http://127.0.0.1:8000/api/${role}`
}

const api = axios.create({ baseURL })

api.interceptors.request.use((req) => {
  if (req.url?.includes('chores')) req.baseURL = req.baseURL?.replace(`/${role}`, '')

  if (user.token) {
    req.headers.Authorization = `Bearer ${user.token}`
  }
  return req
})

export default api
