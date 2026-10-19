<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
import PageHeader from '../Components/PageHeader.vue'
import StatusBadge from '../Components/StatusBadge.vue'
import MonthlyChart from '../Components/MonthlyChart.vue'
import HandlingModal from '../Components/HandlingModal.vue'
import { keterangan } from '../status'

defineProps({
    total: Number,
    problem: Number,
    bkCount: Number,
    byStatus: Object,
    monthly: Array,
    referrals: Array,
    handled: Array,
    problemStudents: Array,
    teachers: Array,
    bks: Array,
})

const role = usePage().props.auth.user.role
const selected = ref(null)
// Guru BK: segarkan data tiap 10 detik supaya rujukan baru langsung muncul
let poll
onMounted(() => {
    if (role !== 'bk') return

    poll = setInterval(() => {
        if (document.hidden || selected.value) return
        router.reload({ only: ['referrals', 'handled', 'problem', 'byStatus', 'monthly'] })
    }, 10000)
})
onBeforeUnmount(() => clearInterval(poll))
</script>

<template>
    <AppLayout>
        <PageHeader title="Dashboard" subtitle="Ringkasan penanganan siswa." />

        <div class="grid gap-4 mb-6" :class="role === 'bk' ? 'grid-cols-2' : 'grid-cols-3'">
            <div v-if="role !== 'bk'" class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-sm text-gray-500">Jumlah siswa</p>
                <p class="text-3xl font-semibold mt-2">{{ total }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-sm text-gray-500">Siswa bermasalah</p>
                <p class="text-3xl font-semibold mt-2 text-blue-700">{{ problem }}</p>
            </div>
            <div v-if="role !== 'bk'" class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-sm text-gray-500">Guru BK</p>
                <p class="text-3xl font-semibold mt-2">{{ bkCount }}</p>
            </div>
            <div v-else class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-sm text-gray-500">Rujukan untuk Anda</p>
                <p class="text-3xl font-semibold mt-2 text-blue-700">{{ referrals.length }}</p>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
            <p class="font-medium text-gray-900">Siswa bermasalah per bulan</p>
            <p class="text-sm text-gray-500 mb-4">12 bulan terakhir</p>
            <MonthlyChart :data="monthly" />
        </div>

        <!-- GURU BK -->
        <template v-if="role === 'bk'">
            <h2 class="text-sm font-medium text-gray-900 mb-2">Rujukan untuk Anda</h2>
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="s in referrals" :key="s.id" class="border-b border-gray-100 last:border-0">
                            <td class="px-4 py-3">{{ s.nis }}</td>
                            <td class="px-4 py-3">{{ s.name }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="s.status" /></td>
                            <td class="px-4 py-3 text-right">
                                <button @click="selected = s" class="text-blue-700 hover:underline">Tangani</button>
                            </td>
                        </tr>
                        <tr v-if="!referrals.length">
                            <td class="px-4 py-3 text-gray-400">Tidak ada rujukan yang menunggu penanganan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h2 class="text-sm font-medium text-gray-900 mb-2">Sudah ditangani</h2>
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="s in handled" :key="s.id" class="border-b border-gray-100 last:border-0">
                            <td class="px-4 py-3">{{ s.nis }}</td>
                            <td class="px-4 py-3">{{ s.name }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="s.status" /></td>
                            <td class="px-4 py-3 text-right">
                                <button @click="selected = s" class="text-gray-500 hover:underline">Detail</button>
                            </td>
                        </tr>
                        <tr v-if="!handled.length">
                            <td class="px-4 py-3 text-gray-400">Belum ada siswa yang sudah ditangani.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <!-- KESISWAAN -->
        <template v-else>
            <h2 class="text-sm font-medium text-gray-900 mb-2">Daftar siswa bermasalah</h2>
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-2 font-medium">NIS</th>
                            <th class="px-4 py-2 font-medium">Nama</th>
                            <th class="px-4 py-2 font-medium">Status</th>
                            <th class="px-4 py-2 font-medium">Keterangan</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in problemStudents" :key="s.id" class="border-b border-gray-100 last:border-0">
                            <td class="px-4 py-3">{{ s.nis }}</td>
                            <td class="px-4 py-3">{{ s.name }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="s.status" /></td>
                            <td class="px-4 py-3 text-gray-500">{{ keterangan(s) }}</td>
                            <td class="px-4 py-3 text-right">
                                <button @click="selected = s" class="hover:underline"
                                    :class="s.can_handle ? 'text-blue-700' : 'text-gray-500'">
                                    {{ s.can_handle ? 'Tangani' : 'Detail' }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!problemStudents.length">
                            <td colspan="5" class="px-4 py-3 text-gray-400">Belum ada siswa bermasalah.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <h2 class="text-sm font-medium text-gray-900 mb-2">Rincian status</h2>
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <tbody>
                    <tr v-for="(count, key) in byStatus" :key="key" class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3"><StatusBadge :status="key" /></td>
                        <td class="px-4 py-3 text-right">{{ count }}</td>
                    </tr>
                    <tr v-if="!Object.keys(byStatus).length">
                        <td class="px-4 py-3 text-gray-400">Belum ada data.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <HandlingModal v-if="selected" :student="selected" :teachers="teachers" :bks="bks" :role="role" @close="selected = null" />
    </AppLayout>
</template>