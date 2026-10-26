<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import PageHeader from '../../Components/PageHeader.vue'
import Modal from '../../Components/Modal.vue'
import ConfirmModal from '../../Components/ConfirmModal.vue'
import ActionMenu from '../../Components/ActionMenu.vue'
import FormField from '../../Components/FormField.vue'
import SearchableSelect from '../../Components/SearchableSelect.vue'
import StatusBadge from '../../Components/StatusBadge.vue'
import HandlingModal from '../../Components/HandlingModal.vue'
import { useCrud } from '../../composables/useCrud'
import { keterangan } from '../../status'

const props = defineProps({
    students: Object,
    teachers: Array,
    bks: Array,
    grades: Array,
    majors: Array,
    nisDigits: Number,
    filters: Object,
})
const role = usePage().props.auth.user.role

const search = ref(props.filters.search ?? '')
let timer
watch(search, (v) => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get('/students', { search: v }, { preserveState: true, replace: true })
    }, 300)
})

const crud = useCrud('/students', { nis: '', name: '', grade_id: '', major_id: '', homeroom_teacher_id: '' })

const teacherOptions = computed(() => props.teachers.map((t) => ({ value: t.id, label: `${t.name} (${t.nip})` })))
const gradeOptions = computed(() => props.grades.map((g) => ({ value: g.id, label: g.name })))
const majorOptions = computed(() => props.majors.map((m) => ({ value: m.id, label: m.name })))
const gradeBk = computed(() => props.grades.find((g) => g.id === crud.form.grade_id)?.bk?.name)

const kelas = (s) => [s.grade?.name, s.major?.name].filter(Boolean).join(' ') || '-'

const selected = ref(null)
</script>

<template>
    <AppLayout>
        <PageHeader
            :title="role === 'bk' ? 'Siswa Bermasalah' : 'Data Siswa'"
            :subtitle="role === 'bk' ? 'Daftar siswa bermasalah beserta riwayat penanganannya.' : 'Kelola data siswa dan penanganannya.'"
        >
            <button v-if="role === 'kesiswaan'" @click="crud.openAdd"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">
                Tambah siswa
            </button>
        </PageHeader>

        <input v-model="search" placeholder="Cari nama atau NIS..."
            class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full max-w-sm mb-4 bg-white focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600" />

        <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-2 font-medium">NIS</th>
                        <th class="px-4 py-2 font-medium">Nama</th>
                        <th class="px-4 py-2 font-medium">Kelas</th>
                        <th class="px-4 py-2 font-medium">Wali kelas</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                        <th class="px-4 py-2 font-medium">Keterangan</th>
                        <th v-if="role === 'kesiswaan'" class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in students.data" :key="s.id" class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3">{{ s.nis }}</td>
                        <td class="px-4 py-3">
                            <button @click="selected = s" class="text-blue-700 hover:underline text-left">{{ s.name }}</button>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ kelas(s) }}</td>
                        <td class="px-4 py-3">{{ s.homeroom_teacher?.name ?? '-' }}</td>
                        <td class="px-4 py-3"><StatusBadge :status="s.status" /></td>
                        <td class="px-4 py-3 text-gray-500">{{ keterangan(s) }}</td>
                        <td v-if="role === 'kesiswaan'" class="px-4 py-3 text-right">
                            <ActionMenu @edit="crud.openEdit(s)" @delete="crud.askDelete(s)" />
                        </td>
                    </tr>
                    <tr v-if="!students.data.length">
                        <td colspan="7" class="px-4 py-3 text-gray-400">Tidak ada data.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex gap-1 mt-4">
            <template v-for="l in students.links" :key="l.label">
                <Link v-if="l.url" :href="l.url" preserve-scroll v-html="l.label"
                    class="px-3 py-1 text-sm border rounded"
                    :class="l.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-200'" />
                <span v-else v-html="l.label" class="px-3 py-1 text-sm text-gray-400" />
            </template>
        </div>

        <Modal v-if="crud.show.value" :title="crud.editId.value ? 'Edit siswa' : 'Tambah siswa'" @close="crud.close">
            <form @submit.prevent="crud.submit" class="space-y-4">
                <FormField label="NIS" required numeric :placeholder="`${nisDigits} digit angka`"
                    v-model="crud.form.nis" :error="crud.form.errors.nis" />
                <FormField label="Nama siswa" required v-model="crud.form.name" :error="crud.form.errors.name" />

                <div class="grid grid-cols-2 gap-4">
                    <SearchableSelect label="Kelas" required placeholder="Pilih kelas" :options="gradeOptions"
                        v-model="crud.form.grade_id" :error="crud.form.errors.grade_id" />
                    <SearchableSelect label="Jurusan" required placeholder="Pilih jurusan" :options="majorOptions"
                        v-model="crud.form.major_id" :error="crud.form.errors.major_id" />
                </div>

                <SearchableSelect label="Wali kelas" required placeholder="Pilih wali kelas" :options="teacherOptions"
                    v-model="crud.form.homeroom_teacher_id" :error="crud.form.errors.homeroom_teacher_id" />

                <div>
                    <label class="block text-sm text-gray-600 mb-1">Guru BK</label>
                    <div class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm bg-gray-50 text-gray-500">
                        {{ gradeBk ?? 'Mengikuti kelas yang dipilih' }}
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="crud.close" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                    <button type="submit" :disabled="crud.form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </Modal>

        <ConfirmModal v-if="crud.deleting.value" title="Hapus siswa" :processing="crud.busy.value"
            :error="crud.deleteError.value" @close="crud.cancelDelete" @confirm="crud.confirmDelete">
            Hapus siswa <strong>{{ crud.deleting.value.name }}</strong>? Seluruh riwayat penanganannya ikut terhapus dan tidak bisa dikembalikan.
        </ConfirmModal>

        <HandlingModal v-if="selected" :student="selected" :teachers="teachers" :bks="bks" :role="role" @close="selected = null" />
    </AppLayout>
</template>