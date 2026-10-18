<template>
  <div>
    <header class="topbar">
      <h1>Admin Inventaris Barang</h1>
      <div class="row">
        <span class="text-muted">{{ user?.name || user?.email }}</span>
        <button class="btn btn-sm btn-secondary" @click="handleLogout">Logout</button>
      </div>
    </header>

    <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
    <div v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</div>

    <div class="card">
      <div class="row between">
        <h2>Daftar Barang</h2>
        <button class="btn" @click="openCreate">+ Tambah Barang</button>
      </div>

      <div v-if="loading" class="text-muted mt-4">Memuat data...</div>

      <div v-else-if="items.length === 0" class="text-muted mt-4">
        Belum ada data barang. Klik "Tambah Barang" untuk menambahkan.
      </div>

      <div v-else class="table-scroll mt-4">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Kode Barang</th>
              <th>Nama Barang</th>
              <th>Kategori</th>
              <th>Stok</th>
              <th>Harga</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.id }}</td>
              <td>{{ item.kode_barang }}</td>
              <td>{{ item.nama_barang }}</td>
              <td>{{ item.kategori }}</td>
              <td>{{ item.stok }}</td>
              <td>{{ formatRupiah(item.harga) }}</td>
              <td>
                <div class="row">
                  <button class="btn btn-sm" @click="openDetail(item)">Detail</button>
                  <button class="btn btn-sm btn-secondary" @click="openEdit(item)">Ubah</button>
                  <button class="btn btn-sm btn-danger" @click="askDelete(item)">Hapus</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Form Tambah / Ubah -->
    <div v-if="showForm" class="modal-backdrop" @click.self="showForm = false">
      <form class="card modal" @submit.prevent="handleSubmit">
        <h2>{{ editing ? 'Ubah Barang' : 'Tambah Barang' }}</h2>

        <div v-if="formError" class="alert alert-error">{{ formError }}</div>

        <div class="field">
          <label for="kode">Kode Barang</label>
          <input id="kode" v-model.trim="form.kode_barang" required />
          <p v-if="fieldErrors.kode_barang" class="text-muted" style="color: var(--danger)">
            {{ fieldErrors.kode_barang[0] }}
          </p>
        </div>

        <div class="field">
          <label for="nama">Nama Barang</label>
          <input id="nama" v-model.trim="form.nama_barang" required />
          <p v-if="fieldErrors.nama_barang" class="text-muted" style="color: var(--danger)">
            {{ fieldErrors.nama_barang[0] }}
          </p>
        </div>

        <div class="field">
          <label for="kategori">Kategori</label>
          <input id="kategori" v-model.trim="form.kategori" required />
          <p v-if="fieldErrors.kategori" class="text-muted" style="color: var(--danger)">
            {{ fieldErrors.kategori[0] }}
          </p>
        </div>

        <div class="field">
          <label for="stok">Stok</label>
          <input id="stok" v-model.number="form.stok" type="number" min="0" step="1" required />
          <p v-if="fieldErrors.stok" class="text-muted" style="color: var(--danger)">
            {{ fieldErrors.stok[0] }}
          </p>
        </div>

        <div class="field">
          <label for="harga">Harga (Rp)</label>
          <input id="harga" v-model.number="form.harga" type="number" min="0" step="0.01" required />
          <p v-if="fieldErrors.harga" class="text-muted" style="color: var(--danger)">
            {{ fieldErrors.harga[0] }}
          </p>
        </div>

        <div class="row">
          <button class="btn" type="submit" :disabled="saving">
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </button>
          <button class="btn btn-secondary" type="button" @click="showForm = false">Batal</button>
        </div>
      </form>
    </div>

    <!-- Detail -->
    <div v-if="detail" class="modal-backdrop" @click.self="detail = null">
      <div class="card modal">
        <h2>Detail Barang</h2>
        <table style="margin-top: 12px">
          <tbody>
            <tr><th>ID</th><td>{{ detail.id }}</td></tr>
            <tr><th>Kode Barang</th><td>{{ detail.kode_barang }}</td></tr>
            <tr><th>Nama Barang</th><td>{{ detail.nama_barang }}</td></tr>
            <tr><th>Kategori</th><td>{{ detail.kategori }}</td></tr>
            <tr><th>Stok</th><td>{{ detail.stok }}</td></tr>
            <tr><th>Harga</th><td>{{ formatRupiah(detail.harga) }}</td></tr>
            <tr><th>Dibuat</th><td>{{ formatTanggal(detail.created_at) }}</td></tr>
            <tr><th>Diubah</th><td>{{ formatTanggal(detail.updated_at) }}</td></tr>
          </tbody>
        </table>
        <button class="btn mt-4" @click="detail = null">Tutup</button>
      </div>
    </div>

    <!-- Konfirmasi hapus -->
    <div v-if="toDelete" class="modal-backdrop" @click.self="toDelete = null">
      <div class="card modal">
        <h2>Hapus Barang</h2>
        <p>
          Yakin ingin menghapus
          <strong>{{ toDelete.nama_barang }}</strong> ({{ toDelete.kode_barang }})?
        </p>
        <div class="row">
          <button class="btn btn-danger" :disabled="deleting" @click="confirmDelete">
            {{ deleting ? 'Menghapus...' : 'Ya, Hapus' }}
          </button>
          <button class="btn btn-secondary" @click="toDelete = null">Batal</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const items = ref([])
const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const errorMessage = ref('')
const flash = reactive({ success: '' })

const showForm = ref(false)
const editing = ref(null)
const formError = ref('')
const fieldErrors = reactive({})

const detail = ref(null)
const toDelete = ref(null)

const user = computed(() => {
  try {
    return JSON.parse(localStorage.getItem('user'))
  } catch {
    return null
  }
})

const form = reactive({
  kode_barang: '',
  nama_barang: '',
  kategori: '',
  stok: 0,
  harga: 0,
})

function formatRupiah(value) {
  const n = Number(value)
  return Number.isFinite(n) ? 'Rp ' + n.toLocaleString('id-ID') : value
}

function formatTanggal(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString('id-ID')
}

function clearFieldErrors() {
  Object.keys(fieldErrors).forEach((k) => delete fieldErrors[k])
}

async function loadItems() {
  loading.value = true
  errorMessage.value = ''
  try {
    const { data } = await api.get('/barang')
    items.value = data.data
  } catch (error) {
    errorMessage.value = 'Gagal memuat data barang.'
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  formError.value = ''
  clearFieldErrors()
  Object.assign(form, { kode_barang: '', nama_barang: '', kategori: '', stok: 0, harga: 0 })
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  formError.value = ''
  clearFieldErrors()
  Object.assign(form, {
    kode_barang: item.kode_barang,
    nama_barang: item.nama_barang,
    kategori: item.kategori,
    stok: item.stok,
    harga: Number(item.harga),
  })
  showForm.value = true
}

// Tampilkan detail satu barang melalui endpoint show (GET /api/barang/{id}).
async function openDetail(item) {
  try {
    const { data } = await api.get(`/barang/${item.id}`)
    detail.value = data.data
  } catch {
    errorMessage.value = 'Gagal memuat detail barang.'
  }
}

async function handleSubmit() {
  formError.value = ''
  clearFieldErrors()
  saving.value = true
  try {
    const payload = {
      kode_barang: form.kode_barang,
      nama_barang: form.nama_barang,
      kategori: form.kategori,
      stok: Number(form.stok),
      harga: Number(form.harga),
    }
    if (editing.value) {
      await api.put(`/barang/${editing.value.id}`, payload)
      flash.success = `Barang "${payload.nama_barang}" berhasil diperbarui.`
    } else {
      await api.post('/barang', payload)
      flash.success = `Barang "${payload.nama_barang}" berhasil ditambahkan.`
    }
    showForm.value = false
    await loadItems()
  } catch (error) {
    if (error.response?.status === 422) {
      formError.value = 'Validasi gagal. Periksa kembali isian Anda.'
      Object.assign(fieldErrors, error.response.data.errors || {})
    } else {
      formError.value = 'Terjadi kesalahan saat menyimpan data.'
    }
  } finally {
    saving.value = false
  }
}

function askDelete(item) {
  toDelete.value = item
}

async function confirmDelete() {
  deleting.value = true
  try {
    await api.delete(`/barang/${toDelete.value.id}`)
    flash.success = `Barang "${toDelete.value.nama_barang}" berhasil dihapus.`
    toDelete.value = null
    await loadItems()
  } catch {
    errorMessage.value = 'Gagal menghapus barang.'
  } finally {
    deleting.value = false
  }
}

async function handleLogout() {
  try {
    // Panggil endpoint logout backend agar token dicabut di server.
    await api.post('/logout')
  } catch {
    // Token mungkin sudah tidak valid di server; abaikan dan tetap logout di klien.
  }
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push({ name: 'login' })
}

onMounted(loadItems)
</script>

<style scoped>
.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
}
h1 { font-size: 19px; margin: 0; }
h2 { font-size: 16px; margin: 0; }
.modal h2 { margin-bottom: 8px; }
</style>