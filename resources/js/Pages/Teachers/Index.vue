<script setup>
import AppLayout from '../../Layouts/AppLayout.vue'
import PageHeader from '../../Components/PageHeader.vue'
import Modal from '../../Components/Modal.vue'
import ConfirmModal from '../../Components/ConfirmModal.vue'
import ActionMenu from '../../Components/ActionMenu.vue'
import FormField from '../../Components/FormField.vue'
import { useCrud } from '../../composables/useCrud'

defineProps({ teachers: Array })

const crud = useCrud('/teachers', { nip: '', name: '' })
</script>

<template>
    <AppLayout>
        <PageHeader title="Wali Kelas" subtitle="Daftar wali kelas yang bisa dipilih saat penanganan.">
            <button @click="crud.openAdd" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">
                Tambah wali kelas
            </button>
        </PageHeader>

        <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-2 font-medium">NIP</th>
                        <th class="px-4 py-2 font-medium">Nama</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in teachers" :key="t.id" class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3">{{ t.nip }}</td>
                        <td class="px-4 py-3">{{ t.name }}</td>
                        <td class="px-4 py-3 text-right">
                            <ActionMenu @edit="crud.openEdit(t)" @delete="crud.askDelete(t)" />
                        </td>
                    </tr>
                    <tr v-if="!teachers.length">
                        <td colspan="3" class="px-4 py-3 text-gray-400">Belum ada data.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Modal v-if="crud.show.value" :title="crud.editId.value ? 'Edit wali kelas' : 'Tambah wali kelas'" @close="crud.close">
            <form @submit.prevent="crud.submit" class="space-y-4">
                <FormField label="NIP" v-model="crud.form.nip" :error="crud.form.errors.nip" />
                <FormField label="Nama wali kelas" v-model="crud.form.name" :error="crud.form.errors.name" />
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="crud.close" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                    <button type="submit" :disabled="crud.form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </Modal>

        <ConfirmModal v-if="crud.deleting.value" title="Hapus wali kelas" :processing="crud.busy.value"
            :error="crud.deleteError.value" @close="crud.cancelDelete" @confirm="crud.confirmDelete">
            Hapus wali kelas <strong>{{ crud.deleting.value.name }}</strong>? Riwayat penanganan tetap ada, tetapi nama wali kelasnya tidak lagi tampil.
        </ConfirmModal>
    </AppLayout>
</template>