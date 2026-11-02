<script setup>
import { computed, ref } from 'vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import PageHeader from '../../Components/PageHeader.vue'
import Modal from '../../Components/Modal.vue'
import ConfirmModal from '../../Components/ConfirmModal.vue'
import ActionMenu from '../../Components/ActionMenu.vue'
import FormField from '../../Components/FormField.vue'
import SearchableSelect from '../../Components/SearchableSelect.vue'
import GroupStudentsModal from '../../Components/GroupStudentsModal.vue'
import { useCrud } from '../../composables/useCrud'

const props = defineProps({ classrooms: Array, grades: Array, majors: Array, bks: Array })

const tab = ref('classrooms')
const tabs = computed(() => [
    { key: 'classrooms', label: 'Kelas', count: props.classrooms.length },
    { key: 'majors', label: 'Jurusan', count: props.majors.length },
    { key: 'grades', label: 'Tingkat & Guru BK', count: props.grades.length },
])

const classCrud = useCrud('/classrooms', { grade_id: '', major_id: '', number: '' })
const majorCrud = useCrud('/majors', { name: '' })
const gradeCrud = useCrud('/grades', { name: '', bk_user_id: '' })

const addLabel = computed(() => ({ classrooms: 'Tambah kelas', majors: 'Tambah jurusan', grades: 'Tambah tingkat' })[tab.value])
const openAdd = () => ({ classrooms: classCrud, majors: majorCrud, grades: gradeCrud })[tab.value].openAdd()

const gradeOptions = computed(() => props.grades.map((g) => ({ value: g.id, label: g.name })))
const majorOptions = computed(() => props.majors.map((m) => ({ value: m.id, label: m.name })))
const bkOptions = computed(() =>
    props.bks.map((b) => ({ value: b.id, label: b.nip ? `${b.name} (${b.nip})` : b.name }))
)

// pratinjau nama kelas dan guru BK saat mengisi form kelas
const previewName = computed(() => {
    const g = props.grades.find((x) => x.id === classCrud.form.grade_id)?.name
    const m = props.majors.find((x) => x.id === classCrud.form.major_id)?.name
    return [g, m, classCrud.form.number].filter(Boolean).join(' ')
})
const previewBk = computed(() => props.grades.find((x) => x.id === classCrud.form.grade_id)?.bk?.name)

const viewUrl = ref(null)

const th = 'px-4 py-2 font-medium'
</script>

<template>
    <AppLayout>
        <PageHeader title="Kelas & Jurusan" subtitle="Atur tingkat, jurusan, dan kelas. Klik jumlah siswa untuk melihat daftar namanya.">
            <button @click="openAdd" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">
                {{ addLabel }}
            </button>
        </PageHeader>

        <div class="flex gap-6 border-b border-gray-200 mb-5 text-sm">
            <button v-for="t in tabs" :key="t.key" type="button" @click="tab = t.key"
                class="pb-3 -mb-px border-b-2"
                :class="tab === t.key ? 'border-blue-600 text-blue-700 font-medium' : 'border-transparent text-gray-500 hover:text-gray-700'">
                {{ t.label }} <span class="ml-1 text-xs text-gray-400">{{ t.count }}</span>
            </button>
        </div>

        <!-- KELAS -->
        <div v-if="tab === 'classrooms'" class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th :class="th">Kelas</th>
                        <th :class="th">Jurusan</th>
                        <th :class="th">Guru BK</th>
                        <th :class="th">Jumlah siswa</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in classrooms" :key="c.id" class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ c.name }}</td>
                        <td class="px-4 py-3">{{ c.major.name }}</td>
                        <td class="px-4 py-3">{{ c.grade.bk?.name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <button @click="viewUrl = `/classrooms/${c.id}/students`" class="text-blue-700 hover:underline">
                                {{ c.students_count }} siswa
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <ActionMenu @edit="classCrud.openEdit(c)" @delete="classCrud.askDelete(c)" />
                        </td>
                    </tr>
                    <tr v-if="!classrooms.length">
                        <td colspan="5" class="px-4 py-3 text-gray-400">
                            Belum ada kelas. Tambahkan tingkat dan jurusan terlebih dahulu, lalu buat kelasnya.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- JURUSAN -->
        <div v-else-if="tab === 'majors'" class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th :class="th">Jurusan</th>
                        <th :class="th">Jumlah kelas</th>
                        <th :class="th">Jumlah siswa</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="m in majors" :key="m.id" class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ m.name }}</td>
                        <td class="px-4 py-3">{{ m.classrooms_count }}</td>
                        <td class="px-4 py-3">
                            <button @click="viewUrl = `/majors/${m.id}/students`" class="text-blue-700 hover:underline">
                                {{ m.students_count }} siswa
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <ActionMenu @edit="majorCrud.openEdit(m)" @delete="majorCrud.askDelete(m)" />
                        </td>
                    </tr>
                    <tr v-if="!majors.length">
                        <td colspan="4" class="px-4 py-3 text-gray-400">Belum ada jurusan.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TINGKAT -->
        <div v-else>
            <p class="text-sm text-gray-500 mb-3">Guru BK ditentukan per tingkat (angkatan). Semua kelas di tingkat yang sama memakai guru BK yang sama.</p>
            <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                            <th :class="th">Tingkat</th>
                            <th :class="th">Guru BK</th>
                            <th :class="th">Jumlah kelas</th>
                            <th :class="th">Jumlah siswa</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="g in grades" :key="g.id" class="border-b border-gray-100 last:border-0">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ g.name }}</td>
                            <td class="px-4 py-3">{{ g.bk?.name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ g.classrooms_count }}</td>
                            <td class="px-4 py-3">
                                <button @click="viewUrl = `/grades/${g.id}/students`" class="text-blue-700 hover:underline">
                                    {{ g.students_count }} siswa
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <ActionMenu @edit="gradeCrud.openEdit(g)" @delete="gradeCrud.askDelete(g)" />
                            </td>
                        </tr>
                        <tr v-if="!grades.length">
                            <td colspan="5" class="px-4 py-3 text-gray-400">Belum ada tingkat. Tambahkan X, XI, dan XII.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal kelas -->
        <Modal v-if="classCrud.show.value" :title="classCrud.editId.value ? 'Edit kelas' : 'Tambah kelas'" @close="classCrud.close">
            <form @submit.prevent="classCrud.submit" class="space-y-4">
                <SearchableSelect label="Tingkat" required placeholder="Pilih tingkat" :options="gradeOptions"
                    v-model="classCrud.form.grade_id" :error="classCrud.form.errors.grade_id" />
                <SearchableSelect label="Jurusan" required placeholder="Pilih jurusan" :options="majorOptions"
                    v-model="classCrud.form.major_id" :error="classCrud.form.errors.major_id" />
                <FormField label="Nomor kelas" required numeric placeholder="Contoh: 1"
                    v-model="classCrud.form.number" :error="classCrud.form.errors.number" />

                <div class="rounded-md bg-gray-50 border border-gray-100 px-3 py-2 text-sm">
                    <p class="text-gray-500">Nama kelas: <span class="font-medium text-gray-900">{{ previewName || '-' }}</span></p>
                    <p class="text-gray-500">Guru BK: <span class="font-medium text-gray-900">{{ previewBk ?? '-' }}</span></p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="classCrud.close" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                    <button type="submit" :disabled="classCrud.form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </Modal>

        <ConfirmModal v-if="classCrud.deleting.value" title="Hapus kelas" :processing="classCrud.busy.value"
            :error="classCrud.deleteError.value" @close="classCrud.cancelDelete" @confirm="classCrud.confirmDelete">
            Hapus kelas <strong>{{ classCrud.deleting.value.name }}</strong>?
        </ConfirmModal>

        <!-- Modal jurusan -->
        <Modal v-if="majorCrud.show.value" :title="majorCrud.editId.value ? 'Edit jurusan' : 'Tambah jurusan'" @close="majorCrud.close">
            <form @submit.prevent="majorCrud.submit" class="space-y-4">
                <FormField label="Nama jurusan" required placeholder="Contoh: RPL"
                    v-model="majorCrud.form.name" :error="majorCrud.form.errors.name" />
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="majorCrud.close" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                    <button type="submit" :disabled="majorCrud.form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </Modal>

        <ConfirmModal v-if="majorCrud.deleting.value" title="Hapus jurusan" :processing="majorCrud.busy.value"
            :error="majorCrud.deleteError.value" @close="majorCrud.cancelDelete" @confirm="majorCrud.confirmDelete">
            Hapus jurusan <strong>{{ majorCrud.deleting.value.name }}</strong>?
        </ConfirmModal>

        <!-- Modal tingkat -->
        <Modal v-if="gradeCrud.show.value" :title="gradeCrud.editId.value ? 'Edit tingkat' : 'Tambah tingkat'" @close="gradeCrud.close">
            <form @submit.prevent="gradeCrud.submit" class="space-y-4">
                <FormField label="Nama tingkat" required placeholder="Contoh: X, XI, XII"
                    v-model="gradeCrud.form.name" :error="gradeCrud.form.errors.name" />
                <div>
                    <SearchableSelect label="Guru BK" required placeholder="Pilih guru BK" :options="bkOptions"
                        v-model="gradeCrud.form.bk_user_id" :error="gradeCrud.form.errors.bk_user_id" />
                    <p class="text-xs text-gray-500 mt-1">Seluruh siswa di tingkat ini otomatis memakai guru BK tersebut.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="gradeCrud.close" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                    <button type="submit" :disabled="gradeCrud.form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </Modal>

        <ConfirmModal v-if="gradeCrud.deleting.value" title="Hapus tingkat" :processing="gradeCrud.busy.value"
            :error="gradeCrud.deleteError.value" @close="gradeCrud.cancelDelete" @confirm="gradeCrud.confirmDelete">
            Hapus tingkat <strong>{{ gradeCrud.deleting.value.name }}</strong>?
        </ConfirmModal>

        <GroupStudentsModal v-if="viewUrl" :url="viewUrl" @close="viewUrl = null" />
    </AppLayout>
</template>