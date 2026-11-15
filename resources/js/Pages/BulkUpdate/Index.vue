<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import { Download } from 'lucide-vue-next'
import AppLayout from '../../Layouts/AppLayout.vue'
import PageHeader from '../../Components/PageHeader.vue'
import SearchableSelect from '../../Components/SearchableSelect.vue'
import ConfirmModal from '../../Components/ConfirmModal.vue'

const props = defineProps({
    students: Object,
    filters: Object,
    classrooms: Array,
    majors: Array,
    teachers: Array,
    nisDigits: Number,
    maxRows: Number,
})

const page = usePage()

/* ---------- Filter tabel ---------- */
const f = reactive({
    search: props.filters.search ?? '',
    classroom_id: props.filters.classroom_id ?? '',
    major_id: props.filters.major_id ?? '',
    homeroom_teacher_id: props.filters.homeroom_teacher_id ?? '',
})

const selected = ref(new Set())
const clean = (obj) => Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== '' && v !== null))

let timer
watch(f, () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        selected.value = new Set() // pilihan dikosongkan agar tidak salah memindahkan siswa
        router.get('/bulk-update', clean(f), { preserveState: true, preserveScroll: true, replace: true })
    }, 300)
})

const classFilterOptions = computed(() => [
    { value: '', label: 'Semua kelas' },
    { value: 'none', label: 'Tanpa kelas' },
    ...props.classrooms.map((c) => ({ value: String(c.id), label: c.name })),
])
const majorFilterOptions = computed(() => [
    { value: '', label: 'Semua jurusan' },
    ...props.majors.map((m) => ({ value: String(m.id), label: m.name })),
])
const teacherFilterOptions = computed(() => [
    { value: '', label: 'Semua wali kelas' },
    ...props.teachers.map((t) => ({ value: String(t.id), label: t.name })),
])

/* ---------- Pilihan siswa ---------- */
const pageIds = computed(() => props.students.data.map((s) => s.id))
const allOnPage = computed(() => pageIds.value.length > 0 && pageIds.value.every((id) => selected.value.has(id)))

const toggle = (id) => {
    const next = new Set(selected.value)
    next.has(id) ? next.delete(id) : next.add(id)
    selected.value = next
}

const togglePage = () => {
    const next = new Set(selected.value)
    pageIds.value.forEach((id) => (allOnPage.value ? next.delete(id) : next.add(id)))
    selected.value = next
}

const loadingIds = ref(false)
const selectAllMatching = async () => {
    loadingIds.value = true
    try {
        const res = await fetch('/bulk-update/ids?' + new URLSearchParams(clean(f)), {
            headers: { Accept: 'application/json' },
        })
        const data = await res.json()
        selected.value = new Set(data.ids)
    } finally {
        loadingIds.value = false
    }
}

/* ---------- Perubahan massal ---------- */
const target = reactive({ classroom_id: '', homeroom_teacher_id: '' })

const classroomOptions = computed(() => [
    { value: '', label: 'Tidak diubah' },
    ...props.classrooms.map((c) => ({ value: c.id, label: c.name })),
])
const teacherOptions = computed(() => [
    { value: '', label: 'Tidak diubah' },
    ...props.teachers.map((t) => ({ value: t.id, label: `${t.name} (${t.nip})` })),
])

const targetClass = computed(() => props.classrooms.find((c) => c.id === target.classroom_id))
const targetTeacher = computed(() => props.teachers.find((t) => t.id === target.homeroom_teacher_id))
const canApply = computed(() => selected.value.size > 0 && (target.classroom_id || target.homeroom_teacher_id))

const confirming = ref(false)
const busy = ref(false)
const moveError = ref('')

const apply = () => {
    router.post(
        '/bulk-update/move',
        {
            student_ids: [...selected.value],
            classroom_id: target.classroom_id || null,
            homeroom_teacher_id: target.homeroom_teacher_id || null,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onStart: () => (busy.value = true),
            onFinish: () => (busy.value = false),
            onSuccess: () => {
                selected.value = new Set()
                target.classroom_id = ''
                target.homeroom_teacher_id = ''
                moveError.value = ''
                confirming.value = false
            },
            onError: (errors) => {
                confirming.value = false
                moveError.value = Object.values(errors)[0]
            },
        }
    )
}

/* ---------- Import siswa baru ---------- */
const result = computed(() => page.props.flash.import_result)
const fileInput = ref(null)
const form = useForm({ file: null, dry_run: false })

const run = (dry) => {
    form.dry_run = dry
    form.post('/bulk-update', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (p) => {
            // setelah impor sungguhan yang berhasil, kosongkan pilihan file
            if (!dry && p.props.flash.import_result?.ok) {
                form.file = null
                if (fileInput.value) fileInput.value.value = ''
            }
        },
    })
}

const inputClass =
    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600'
</script>

<template>
    <AppLayout>
        <!-- BAGIAN 1: KENAIKAN KELAS & WALI KELAS -->
        <PageHeader title="Kenaikan Kelas & Wali Kelas"
            subtitle="Pindahkan siswa naik kelas dan ganti wali kelas langsung di sistem. Siswa yang sudah dropout tidak ditampilkan." />

        <!-- Filter -->
        <div class="grid md:grid-cols-4 gap-3 mb-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Cari</label>
                <input v-model="f.search" placeholder="Nama atau NIS..." :class="inputClass" />
            </div>
            <SearchableSelect label="Kelas" :options="classFilterOptions" v-model="f.classroom_id" />
            <SearchableSelect label="Jurusan" :options="majorFilterOptions" v-model="f.major_id" />
            <SearchableSelect label="Wali kelas" :options="teacherFilterOptions" v-model="f.homeroom_teacher_id" />
        </div>

        <!-- Panel perubahan (muncul setelah siswa dipilih) -->
        <div v-if="selected.size" class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3 text-sm">
                <p class="text-blue-900">
                    <strong>{{ selected.size }}</strong> siswa dipilih
                    <button v-if="selected.size < students.total" type="button" :disabled="loadingIds"
                        @click="selectAllMatching" class="ml-2 text-blue-700 underline disabled:opacity-50">
                        {{ loadingIds ? 'Memuat...' : `Pilih semua ${students.total} siswa hasil filter` }}
                    </button>
                </p>
                <button type="button" @click="selected = new Set()" class="text-gray-500 hover:text-gray-700">Hapus pilihan</button>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <SearchableSelect label="Pindahkan ke kelas" placeholder="Tidak diubah"
                    :options="classroomOptions" v-model="target.classroom_id" />
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Guru BK (otomatis)</label>
                    <div class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm bg-white text-gray-500">
                        {{ targetClass ? (targetClass.bk ?? '-') : 'Mengikuti kelas tujuan' }}
                    </div>
                </div>
                <SearchableSelect label="Wali kelas baru" placeholder="Tidak diubah"
                    :options="teacherOptions" v-model="target.homeroom_teacher_id" />
            </div>

            <p v-if="moveError" class="text-sm text-red-600 mt-3">{{ moveError }}</p>

            <div class="mt-4 flex justify-end">
                <button type="button" :disabled="!canApply" @click="confirming = true"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm px-4 py-2 rounded-md">
                    Terapkan
                </button>
            </div>
        </div>

        <!-- Tabel siswa -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th class="pl-4 pr-2 py-2 w-8">
                            <input type="checkbox" :checked="allOnPage" @change="togglePage" class="rounded border-gray-300" />
                        </th>
                        <th class="px-3 py-2 font-medium">NIS</th>
                        <th class="px-3 py-2 font-medium">Nama</th>
                        <th class="px-3 py-2 font-medium">Kelas</th>
                        <th class="px-3 py-2 font-medium">Jurusan</th>
                        <th class="px-3 py-2 font-medium">Wali kelas</th>
                        <th class="px-3 py-2 font-medium">Guru BK</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in students.data" :key="s.id" class="border-b border-gray-100 last:border-0"
                        :class="selected.has(s.id) ? 'bg-blue-50/60' : ''">
                        <td class="pl-4 pr-2 py-2">
                            <input type="checkbox" :checked="selected.has(s.id)" @change="toggle(s.id)" class="rounded border-gray-300" />
                        </td>
                        <td class="px-3 py-2">{{ s.nis }}</td>
                        <td class="px-3 py-2">{{ s.name }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ s.classroom ?? '-' }}</td>
                        <td class="px-3 py-2">{{ s.major ?? '-' }}</td>
                        <td class="px-3 py-2">{{ s.teacher ?? '-' }}</td>
                        <td class="px-3 py-2">{{ s.bk ?? '-' }}</td>
                    </tr>
                    <tr v-if="!students.data.length">
                        <td colspan="7" class="px-4 py-3 text-gray-400">Tidak ada siswa yang cocok dengan filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
            <p class="text-xs text-gray-500">
                Menampilkan {{ students.from ?? 0 }}–{{ students.to ?? 0 }} dari {{ students.total }} siswa
            </p>
            <div class="flex gap-1">
                <template v-for="l in students.links" :key="l.label">
                    <Link v-if="l.url" :href="l.url" preserve-state preserve-scroll v-html="l.label"
                        class="px-3 py-1 text-sm border rounded"
                        :class="l.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-200'" />
                    <span v-else v-html="l.label" class="px-3 py-1 text-sm text-gray-400" />
                </template>
            </div>
        </div>

        <ConfirmModal v-if="confirming" title="Terapkan perubahan" confirm-label="Terapkan" tone="primary"
            :processing="busy" @close="confirming = false" @confirm="apply">
            <p><strong>{{ selected.size }} siswa</strong> akan diperbarui:</p>
            <ul class="mt-2 list-disc pl-5 space-y-1">
                <li v-if="targetClass">Kelas menjadi <strong>{{ targetClass.name }}</strong></li>
                <li v-if="targetClass">Guru BK otomatis menjadi <strong>{{ targetClass.bk ?? '-' }}</strong></li>
                <li v-if="targetTeacher">Wali kelas menjadi <strong>{{ targetTeacher.name }}</strong></li>
            </ul>
        </ConfirmModal>

        <!-- BAGIAN 2: IMPORT SIWA BARU -->
        <PageHeader class="mt-14" title="Import Siswa Baru"
            subtitle="Khusus menambahkan siswa baru lewat Excel. Untuk kenaikan kelas, gunakan tabel di atas.">
            <a href="/bulk-update/template"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">
                <Download class="w-4 h-4" /> Unduh template
            </a>
        </PageHeader>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
                <div>
                    <p class="font-medium text-gray-900">Unggah file</p>
                    <p class="text-sm text-gray-500">File Excel (.xlsx) yang sudah diisi dari template.</p>
                </div>

                <div>
                    <input ref="fileInput" type="file" accept=".xlsx" @change="form.file = $event.target.files[0] ?? null"
                        class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:text-blue-700 hover:file:bg-blue-100" />
                    <p v-if="form.errors.file" class="text-xs text-red-600 mt-1">{{ form.errors.file }}</p>
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="button" :disabled="!form.file || form.processing" @click="run(true)"
                        class="border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed text-sm px-4 py-2 rounded-md">
                        Periksa file
                    </button>
                    <button type="button" :disabled="!form.file || form.processing" @click="run(false)"
                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm px-4 py-2 rounded-md">
                        Impor
                    </button>
                </div>
                <p v-if="form.processing" class="text-sm text-gray-500">Memproses file...</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="font-medium text-gray-900 mb-3">Panduan</p>
                <ol class="list-decimal pl-5 space-y-2 text-sm text-gray-600">
                    <li>Unduh template terbaru, lalu isi sheet <strong>Template</strong> mulai baris 2. Arahkan kursor ke judul kolom untuk melihat note.</li>
                    <li>Isi <strong>NIS</strong> ({{ nisDigits }} digit angka, belum terdaftar), <strong>Nama Siswa</strong>, <strong>Kelas</strong> (pilih dari dropdown, contoh X RPL 1), dan <strong>NIP Wali Kelas</strong>.</li>
                    <li>Kolom <strong>Jurusan</strong> dan <strong>Guru BK</strong> (abu-abu) terisi otomatis dari kelas. Jangan diubah.</li>
                    <li>Gunakan <strong>Periksa file</strong> untuk mengecek isi file tanpa menyimpan apa pun.</li>
                    <li>Jika ada satu saja baris yang salah, <strong>seluruh impor dibatalkan</strong> dan kesalahannya ditampilkan di bawah.</li>
                    <li>Maksimal {{ maxRows }} baris per file.</li>
                </ol>
            </div>
        </div>

        <div v-if="result" class="mt-6">
            <div v-if="result.ok" class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 text-sm">
                <p class="font-medium">{{ result.dry_run ? 'File valid dan siap diimpor.' : 'Impor berhasil.' }}</p>
                <p class="mt-1">
                    {{ result.created }} siswa baru {{ result.dry_run ? 'akan ditambahkan' : 'ditambahkan' }}.
                </p>
            </div>

            <div v-else class="bg-white border border-red-200 rounded-lg overflow-hidden">
                <div class="bg-red-50 text-red-800 px-4 py-3 text-sm">
                    <p class="font-medium">
                        {{ result.dry_run ? 'Pemeriksaan selesai:' : 'Impor dibatalkan:' }}
                        ditemukan {{ result.error_count }} kesalahan.
                    </p>
                    <p v-if="!result.dry_run" class="mt-1">Tidak ada data yang diubah. Perbaiki file lalu unggah kembali.</p>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-100 sticky top-0">
                                <th class="px-4 py-2 font-medium w-20">Baris</th>
                                <th class="px-4 py-2 font-medium w-40">Kolom</th>
                                <th class="px-4 py-2 font-medium">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(e, i) in result.errors" :key="i" class="border-b border-gray-100 last:border-0">
                                <td class="px-4 py-2">{{ e.row || '-' }}</td>
                                <td class="px-4 py-2">{{ e.column }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ e.message }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="result.error_count > result.errors.length" class="px-4 py-2 text-xs text-gray-500 border-t border-gray-100">
                    Menampilkan {{ result.errors.length }} dari {{ result.error_count }} kesalahan. Perbaiki yang tampil lalu periksa ulang.
                </p>
            </div>
        </div>
    </AppLayout>
</template>