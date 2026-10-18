import axios from 'axios'

// Base URL diambil dari environment variable VITE_API_BASE_URL (lihat .env.example),
// TIDAK ditulis mati di dalam komponen.
const baseURL = import.meta.env.VITE_API_BASE_URL || '/api'

const api = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

// Request interceptor: sisipkan token Sanctum pada setiap request yang butuh auth.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor: tangani 401 (token tidak valid / kedaluwarsa).
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      // Hindari loop infinite jika sudah berada di halaman login.
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  },
)

export default api