<template>
  <div class="login-wrap">
    <form class="card login-card" @submit.prevent="handleLogin">
      <h1>Admin Inventaris Barang</h1>
      <p class="text-muted">Silakan login untuk mengelola data barang.</p>

      <div v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</div>

      <div class="field">
        <label for="email">Email</label>
        <input
          id="email"
          v-model.trim="form.email"
          type="email"
          placeholder="admin@inventaris.test"
          required
        />
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          placeholder="password123"
          required
        />
      </div>

      <button class="btn" type="submit" :disabled="loading" style="width: 100%">
        {{ loading ? 'Memproses...' : 'Login' }}
      </button>

      <p class="text-muted mt-4">
        Akun uji dari seeder: <strong>{{ adminEmail }}</strong> / password123
      </p>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const adminEmail = import.meta.env.VITE_ADMIN_EMAIL || 'admin@inventaris.test'

const form = reactive({ email: '', password: '' })
const loading = ref(false)
const errorMessage = ref('')

async function handleLogin() {
  errorMessage.value = ''
  loading.value = true
  try {
    const { data } = await api.post('/login', {
      email: form.email,
      password: form.password,
    })
    // Simpan token dari backend, lalu kirim ulang di setiap request.
    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))
    router.push({ name: 'barang' })
  } catch (error) {
    errorMessage.value =
      error.response?.status === 422
        ? 'Email dan password wajib diisi.'
        : 'Login gagal. Periksa kembali email dan password Anda.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
.login-card { width: 100%; max-width: 400px; }
h1 { font-size: 20px; margin: 0 0 4px; }
</style>