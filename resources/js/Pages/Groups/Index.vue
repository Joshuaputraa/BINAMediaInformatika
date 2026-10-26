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

const props = defineProps({ grades: Array, majors: Array, bks: Array })

const gradeCrud = useCrud('/grades', { name: '', bk_user_id: '' })
const majorCrud = useCrud('/majors', { name: '' })

const bkOptions = computed(() =>
    props.bks.map((b) => ({ value: b.id, label: b.nip ? `${b.name} (${b.nip})` : b.name }))
)

const viewUrl = ref(null)
</script>

<template>
    <AppLayout>
        <PageHeader title="Kelas" subtitle="Setiap kelas memiliki satu guru BK. Klik jumlah siswa untuk melihat daftarnya.">
            <button @click="gradeCrud.openAdd" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">
                Tambah kelas
            </button>
        </PageHeader>

        <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto mb-10">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-2 font-medium">Kelas</th>
                        <th class="px-4 py-2 font-medium">Guru BK</th>
                        <th class="px-4 py-2 font-medium">Jumlah siswa</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="g in grades" :key="g.id" class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ g.name }}</td>
                        <td class="px-4 py-3">{{ g.bk?.name ?? '-' }}</td>
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
                        <td colspan="4" class="px-4 py-3 text-gray-400">Belum ada kelas. Tambahkan kelas terlebih dahulu.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <PageHeader title="Jurusan" subtitle="Klik jumlah siswa untuk melihat daftarnya.">
            <button @click="majorCrud.openAdd" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">
                Tambah jurusan
            </button>
        </PageHeader>

        <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-2 font-medium">Jurusan</th>
                        <th class="px-4 py-2 font-medium">Jumlah siswa</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="m in majors" :key="m.id" class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ m.name }}</td>
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
                        <td colspan="3" class="px-4 py-3 text-gray-400">Belum ada jurusan.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Kelas -->
        <Modal v-if="gradeCrud.show.value" :title="gradeCrud.editId.value ? 'Edit kelas' : 'Tambah kelas'" @close="gradeCrud.close">
            <form @submit.prevent="gradeCrud.submit" class="space-y-4">
                <FormField label="Nama kelas" required placeholder="Contoh: X, XI, XII"
                    v-model="gradeCrud.form.name" :error="gradeCrud.form.errors.name" />
                <div>
                    <SearchableSelect label="Guru BK" required placeholder="Pilih guru BK" :options="bkOptions"
                        v-model="gradeCrud.form.bk_user_id" :error="gradeCrud.form.errors.bk_user_id" />
                    <p class="text-xs text-gray-500 mt-1">Seluruh siswa kelas ini otomatis memakai guru BK tersebut.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="gradeCrud.close" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                    <button type="submit" :disabled="gradeCrud.form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </Modal>

        <ConfirmModal v-if="gradeCrud.deleting.value" title="Hapus kelas" :processing="gradeCrud.busy.value"
            :error="gradeCrud.deleteError.value" @close="gradeCrud.cancelDelete" @confirm="gradeCrud.confirmDelete">
            Hapus kelas <strong>{{ gradeCrud.deleting.value.name }}</strong>?
        </ConfirmModal>

        <!-- Jurusan -->
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

        <GroupStudentsModal v-if="viewUrl" :url="viewUrl" @close="viewUrl = null" />
    </AppLayout>
</template>