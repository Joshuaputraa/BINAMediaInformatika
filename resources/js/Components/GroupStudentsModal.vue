<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import StatusBadge from './StatusBadge.vue'

const props = defineProps({ url: String })
const emit = defineEmits(['close'])

const data = ref(null)
const loading = ref(true)
const failed = ref(false)
const q = ref('')

const filtered = computed(() => {
    const term = q.value.trim().toLowerCase()
    const list = data.value?.students ?? []
    return term ? list.filter((s) => s.name.toLowerCase().includes(term) || s.nis.includes(term)) : list
})

onMounted(async () => {
    try {
        const res = await fetch(props.url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        if (!res.ok) throw new Error('gagal')
        data.value = await res.json()
    } catch {
        failed.value = true
    } finally {
        loading.value = false
    }
})

const onKey = (e) => e.key === 'Escape' && emit('close')
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<template>
    <div class="fixed inset-0 z-40 bg-black/30 flex items-center justify-center px-4" @click.self="emit('close')">
        <div class="bg-white rounded-lg w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">
            <div class="px-6 pt-5 pb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-gray-900">{{ data?.title ?? 'Daftar siswa' }}</h2>
                    <p v-if="data" class="text-xs text-gray-500 mt-1">{{ data.students.length }} siswa</p>
                </div>
                <button type="button" @click="emit('close')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <div class="px-6 pb-3">
                <input v-model="q" placeholder="Cari nama atau NIS..."
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600" />
            </div>

            <div class="flex-1 overflow-y-auto border-t border-gray-100">
                <p v-if="loading" class="px-6 py-4 text-sm text-gray-400">Memuat...</p>
                <p v-else-if="failed" class="px-6 py-4 text-sm text-red-600">Gagal memuat data. Muat ulang halaman lalu coba lagi.</p>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-100 sticky top-0">
                            <th class="px-6 py-2 font-medium">NIS</th>
                            <th class="px-3 py-2 font-medium">Nama</th>
                            <th class="px-3 py-2 font-medium">{{ data.extraLabel }}</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in filtered" :key="s.id" class="border-b border-gray-100 last:border-0">
                            <td class="px-6 py-2">{{ s.nis }}</td>
                            <td class="px-3 py-2">{{ s.name }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ s.extra ?? '-' }}</td>
                            <td class="px-3 py-2"><StatusBadge :status="s.status" /></td>
                        </tr>
                        <tr v-if="!filtered.length">
                            <td colspan="4" class="px-6 py-4 text-gray-400">Tidak ada siswa.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>