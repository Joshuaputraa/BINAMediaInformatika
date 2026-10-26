<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import StatusBadge from './StatusBadge.vue'
import { ACTIONS } from '../status'

const props = defineProps({
    student: Object,
    teachers: { type: Array, default: () => [] },
    bks: { type: Array, default: () => [] }, // tidak dipakai lagi (guru BK mengikuti kelas), dipertahankan agar halaman lain tidak perlu diubah
    role: String,
})
const emit = defineEmits(['close'])

const isBk = computed(() => props.role === 'bk')
const bkLocked = computed(() => !!props.student.bk_handled)
const waliLocked = computed(() => !!props.student.wali_handled)
const noWali = computed(() => !props.student.homeroom_teacher_id)
const noBk = computed(() => !props.student.bk_user_id)
const spLevel = computed(() => props.student.sp_level ?? 0)

const tab = ref(props.student.can_handle ? 'form' : 'history')
const tabs = computed(() => [
    { key: 'form', label: 'Penanganan' },
    { key: 'history', label: `Riwayat (${props.student.handlings.length})` },
])

const form = useForm({
    type: isBk.value ? 'bk' : '',
    homeroom_teacher_id: '',
    notes: '',
})

// wali kelas selalu mengikuti data siswa
watch(() => form.type, (t) => {
    form.homeroom_teacher_id = t === 'wali_kelas' ? (props.student.homeroom_teacher_id ?? '') : ''
})

const spNote = (n) => {
    if (n > spLevel.value) return ''
    return props.student.handlings.some((h) => h.type === `sp${n}`) ? 'Sudah diberikan' : 'Terlewati'
}

// "note" terisi = pilihan terkunci, isinya alasan singkat
const groups = computed(() => [
    {
        title: 'Pengawasan',
        cols: 'grid-cols-2',
        items: [
            {
                value: 'wali_kelas',
                label: 'Wali kelas',
                note: waliLocked.value ? 'Sudah ditangani' : noWali.value ? 'Belum ada wali kelas' : '',
            },
            {
                value: 'bk',
                label: 'Rujuk ke guru BK',
                note: bkLocked.value ? 'Sudah ditangani' : noBk.value ? 'Belum ada guru BK' : '',
            },
        ],
    },
    {
        title: 'Sanksi',
        cols: 'grid-cols-4',
        items: [
            { value: 'sp1', label: 'SP 1', note: spNote(1) },
            { value: 'sp2', label: 'SP 2', note: spNote(2) },
            { value: 'sp3', label: 'SP 3', note: spNote(3) },
            { value: 'dropout', label: 'Dropout' },
        ],
    },
])

const optionClass = (o) => {
    if (o.note) return 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed'
    if (form.type === o.value) {
        return o.value === 'dropout'
            ? 'border-gray-900 bg-gray-900 text-white'
            : 'border-blue-600 bg-blue-50 text-blue-800'
    }
    return 'border-gray-200 text-gray-700 hover:border-blue-400'
}

const notesLabel = computed(() => {
    if (isBk.value) return 'Penanganan guru BK'
    if (form.type === 'bk') return 'Catatan untuk guru BK'
    return 'Penanganan yang sudah dilakukan'
})

const teacherName = computed(
    () =>
        props.student.homeroom_teacher?.name ??
        (props.teachers.find((t) => t.id === props.student.homeroom_teacher_id)?.name ?? '-')
)

const bkInfo = computed(() => {
    const name = props.student.bk?.name
    if (!name) return '-'
    if (props.student.bk_handled) return `${name} (sudah menangani)`
    if (props.student.status === 'bk') return `${name} (rujukan)`
    return name
})

const classInfo = computed(() => {
    const parts = [props.student.grade?.name, props.student.major?.name].filter(Boolean)
    return parts.length ? parts.join(' ') : '-'
})

const dropout = computed(() =>
    props.student.status === 'dropout' ? props.student.handlings.find((h) => h.type === 'dropout') : null
)

const readOnlyMessage = computed(() => {
    if (props.student.status === 'dropout') return ''
    if (isBk.value) {
        return props.student.bk_handled
            ? 'Siswa ini sudah ditangani guru BK.'
            : 'Anda hanya bisa mengisi penanganan setelah kesiswaan merujuk siswa ini kepada Anda.'
    }
    return ''
})

const fmt = (d) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })

const label = (h) => {
    if (h.type === 'bk' && h.user?.role === 'kesiswaan') return 'Dirujuk ke guru BK'
    return ACTIONS.find((a) => a.value === h.type)?.label
}

const dot = (type) =>
    ({
        wali_kelas: 'bg-blue-500',
        bk: 'bg-yellow-500',
        sp1: 'bg-red-500',
        sp2: 'bg-red-500',
        sp3: 'bg-red-500',
        dropout: 'bg-gray-900',
    })[type] ?? 'bg-gray-400'

const meta = (h) =>
    [h.teacher?.name, h.user ? `oleh ${h.user.name}` : null].filter(Boolean).join(' · ')

const submit = () =>
    form.post(`/students/${props.student.id}/handlings`, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    })

const onKey = (e) => e.key === 'Escape' && emit('close')
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const field =
    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600'
</script>

<template>
    <div class="fixed inset-0 z-40 bg-black/30 flex items-center justify-center px-4" @click.self="emit('close')">
        <div class="bg-white rounded-lg w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="px-6 pt-5 pb-4 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold text-gray-900 truncate">{{ student.name }}</h2>
                        <StatusBadge :status="student.status" />
                    </div>
                    <p class="text-xs text-gray-500 mt-1">NIS {{ student.nis }}</p>
                </div>
                <button type="button" @click="emit('close')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <dl class="px-6 py-3 grid grid-cols-3 gap-4 text-xs bg-gray-50 border-y border-gray-100">
                <div>
                    <dt class="text-gray-500">Kelas</dt>
                    <dd class="text-gray-900 mt-0.5">{{ classInfo }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Wali kelas</dt>
                    <dd class="text-gray-900 mt-0.5">{{ teacherName }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Guru BK</dt>
                    <dd class="text-gray-900 mt-0.5">{{ bkInfo }}</dd>
                </div>
            </dl>

            <!-- Tab (hanya kalau siswa bisa ditangani) -->
            <div v-if="student.can_handle" class="px-6 border-b border-gray-100 flex gap-6 text-sm">
                <button v-for="t in tabs" :key="t.key" type="button" @click="tab = t.key"
                    class="py-3 -mb-px border-b-2"
                    :class="tab === t.key ? 'border-blue-600 text-blue-700 font-medium' : 'border-transparent text-gray-500 hover:text-gray-700'">
                    {{ t.label }}
                </button>
            </div>

            <!-- Isi -->
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div v-if="student.status === 'dropout'" class="mb-5 text-sm bg-gray-900 text-white rounded-md px-3 py-2">
                    <p class="font-medium">Siswa ini sudah di-dropout.</p>
                    <p v-if="dropout" class="text-xs text-gray-300 mt-1">{{ fmt(dropout.created_at) }} · {{ dropout.notes }}</p>
                </div>

                <!-- Form penanganan -->
                <div v-if="tab === 'form' && student.can_handle" class="space-y-5">
                    <template v-if="!isBk">
                        <div v-for="g in groups" :key="g.title">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-2">{{ g.title }}</p>
                            <div class="grid gap-2" :class="g.cols">
                                <button v-for="o in g.items" :key="o.value" type="button"
                                    :disabled="!!o.note" @click="form.type = o.value"
                                    class="rounded-md border px-3 py-2 text-left text-sm transition"
                                    :class="optionClass(o)">
                                    <span class="block font-medium">{{ o.label }}</span>
                                    <span v-if="o.note" class="block text-xs">{{ o.note }}</span>
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.type" class="text-xs text-red-600">{{ form.errors.type }}</p>

                        <div v-if="form.type === 'wali_kelas'">
                            <label class="block text-sm text-gray-600 mb-1">Wali kelas</label>
                            <select v-model="form.homeroom_teacher_id" disabled :class="field" class="bg-gray-50 text-gray-500 cursor-not-allowed">
                                <option value="">Belum ditentukan</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }} ({{ t.nip }})</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Ditentukan saat data siswa diinput.</p>
                        </div>

                        <div v-if="form.type === 'bk'">
                            <label class="block text-sm text-gray-600 mb-1">Guru BK</label>
                            <select disabled :class="field" class="bg-gray-50 text-gray-500 cursor-not-allowed">
                                <option>{{ student.bk?.name ?? 'Belum ditentukan' }}</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Ditentukan dari kelas siswa.</p>
                        </div>
                    </template>

                    <div v-if="form.type">
                        <label class="block text-sm text-gray-600 mb-1">{{ notesLabel }}</label>
                        <textarea v-model="form.notes" rows="3" :class="field"></textarea>
                        <p v-if="form.errors.notes" class="text-xs text-red-600 mt-1">{{ form.errors.notes }}</p>
                    </div>
                    <p v-else-if="!isBk" class="text-sm text-gray-400">Pilih tindakan untuk mengisi catatan.</p>
                </div>

                <!-- Riwayat -->
                <div v-else>
                    <p v-if="readOnlyMessage" class="mb-5 text-sm text-gray-500 bg-gray-50 border border-gray-100 rounded-md px-3 py-2">
                        {{ readOnlyMessage }}
                    </p>

                    <ul v-if="student.handlings.length" class="relative border-l border-gray-200 ml-1 space-y-5">
                        <li v-for="h in student.handlings" :key="h.id" class="relative pl-5">
                            <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full ring-4 ring-white" :class="dot(h.type)"></span>
                            <div class="flex items-baseline justify-between gap-3">
                                <p class="text-sm font-medium text-gray-900">{{ label(h) }}</p>
                                <p class="text-xs text-gray-400 shrink-0">{{ fmt(h.created_at) }}</p>
                            </div>
                            <p class="text-sm text-gray-600 mt-0.5">{{ h.notes }}</p>
                            <p v-if="meta(h)" class="text-xs text-gray-400 mt-1">{{ meta(h) }}</p>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-400">Belum ada riwayat penanganan.</p>
                </div>
            </div>

            <!-- Footer -->
            <div v-if="tab === 'form' && student.can_handle" class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" @click="emit('close')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                <button type="button" @click="submit" :disabled="!form.type || form.processing"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm px-4 py-2 rounded-md">
                    {{ form.type === 'bk' && !isBk ? 'Kirim rujukan' : 'Simpan' }}
                </button>
            </div>
        </div>
    </div>
</template>