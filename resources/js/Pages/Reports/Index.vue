<script setup>
import { computed, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Download } from 'lucide-vue-next'
import AppLayout from '../../Layouts/AppLayout.vue'
import PageHeader from '../../Components/PageHeader.vue'
import StatusBadge from '../../Components/StatusBadge.vue'
import { ACTIONS } from '../../status'

const props = defineProps({ rows: Object, summary: Object, filters: Object })

const form = reactive({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    type: props.filters.type ?? '',
})

const clean = (obj) => Object.fromEntries(Object.entries(obj).filter(([, v]) => v))

const apply = () => router.get('/reports', clean(form), { preserveState: true, replace: true })
const reset = () => {
    form.from = ''
    form.to = ''
    form.type = ''
    apply()
}

// export mengikuti filter yang sedang diterapkan
const pdfUrl = computed(() => '/reports/pdf?' + new URLSearchParams(clean(props.filters)).toString())

const input =
    'border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600'
</script>

<template>
    <AppLayout>
        <PageHeader title="Laporan"
    :subtitle="$page.props.auth.user.role === 'bk' ? 'Riwayat penanganan siswa yang dirujuk kepada Anda.' : 'Riwayat penanganan siswa berdasarkan periode.'">
            <a :href="pdfUrl" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">
                <Download class="w-4 h-4" /> Export PDF
            </a>
        </PageHeader>

        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Dari</label>
                <input type="date" v-model="form.from" :class="input" />
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sampai</label>
                <input type="date" v-model="form.to" :class="input" />
                <p v-if="$page.props.errors.to" class="text-xs text-red-600 mt-1">{{ $page.props.errors.to }}</p>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tindakan</label>
                <select v-model="form.type" :class="input">
                    <option value="">Semua</option>
                    <option v-for="a in ACTIONS" :key="a.value" :value="a.value">{{ a.label }}</option>
                </select>
            </div>
            <button @click="apply" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Terapkan</button>
            <button @click="reset" class="text-sm text-gray-600 hover:text-gray-800 px-2 py-2">Reset</button>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
            <p><span class="text-gray-500">Siswa tercatat</span> <span class="font-semibold ml-1">{{ summary.students }}</span></p>
            <p><span class="text-gray-500">Total penanganan</span> <span class="font-semibold ml-1">{{ summary.total }}</span></p>
            <span v-for="(n, t) in summary.byType" :key="t" class="inline-flex items-center gap-1">
                <StatusBadge :status="t" /> <span class="text-gray-500">{{ n }}</span>
            </span>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-2 font-medium">Tanggal</th>
                        <th class="px-4 py-2 font-medium">NIS</th>
                        <th class="px-4 py-2 font-medium">Nama</th>
                        <th class="px-4 py-2 font-medium">Tindakan</th>
                        <th class="px-4 py-2 font-medium">Wali kelas</th>
                        <th class="px-4 py-2 font-medium">Guru BK</th>
                        <th class="px-4 py-2 font-medium">Dicatat oleh</th>
                        <th class="px-4 py-2 font-medium">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in rows.data" :key="r.id" class="border-b border-gray-100 last:border-0 align-top">
                        <td class="px-4 py-3 whitespace-nowrap">{{ r.date }}</td>
                        <td class="px-4 py-3">{{ r.nis }}</td>
                        <td class="px-4 py-3">{{ r.student }}</td>
                        <td class="px-4 py-3"><StatusBadge :status="r.type" /></td>
                        <td class="px-4 py-3">{{ r.teacher ?? '-' }}</td>
                        <td class="px-4 py-3">{{ r.bk ?? '-' }}</td>
                        <td class="px-4 py-3">{{ r.by }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ r.notes }}</td>
                    </tr>
                    <tr v-if="!rows.data.length">
                        <td colspan="8" class="px-4 py-3 text-gray-400">Tidak ada data pada periode ini.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex gap-1 mt-4">
            <template v-for="l in rows.links" :key="l.label">
                <Link v-if="l.url" :href="l.url" preserve-scroll v-html="l.label"
                    class="px-3 py-1 text-sm border rounded"
                    :class="l.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-200'" />
                <span v-else v-html="l.label" class="px-3 py-1 text-sm text-gray-400" />
            </template>
        </div>
    </AppLayout>
</template>