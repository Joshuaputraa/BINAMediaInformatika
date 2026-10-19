<script setup>
import AppLayout from '../../Layouts/AppLayout.vue'
import PageHeader from '../../Components/PageHeader.vue'
import Modal from '../../Components/Modal.vue'
import ConfirmModal from '../../Components/ConfirmModal.vue'
import ActionMenu from '../../Components/ActionMenu.vue'
import FormField from '../../Components/FormField.vue'
import { useCrud } from '../../composables/useCrud'

defineProps({ bks: Array })

const crud = useCrud('/bk-teachers', { nip: '', name: '', username: '', password: '' })
</script>

<template>
    <AppLayout>
        <PageHeader title="Guru BK" subtitle="Akun guru BK yang bisa menerima rujukan siswa.">
            <button @click="crud.openAdd" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">
                Tambah guru BK
            </button>
        </PageHeader>

        <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-2 font-medium">NIP</th>
                        <th class="px-4 py-2 font-medium">Nama</th>
                        <th class="px-4 py-2 font-medium">Username</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in bks" :key="b.id" class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3">{{ b.nip ?? '-' }}</td>
                        <td class="px-4 py-3">{{ b.name }}</td>
                        <td class="px-4 py-3">{{ b.username }}</td>
                        <td class="px-4 py-3 text-right">
                            <ActionMenu @edit="crud.openEdit(b)" @delete="crud.askDelete(b)" />
                        </td>
                    </tr>
                    <tr v-if="!bks.length">
                        <td colspan="4" class="px-4 py-3 text-gray-400">Belum ada data.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Modal v-if="crud.show.value" :title="crud.editId.value ? 'Edit guru BK' : 'Tambah guru BK'" @close="crud.close">
            <form @submit.prevent="crud.submit" class="space-y-4">
                <FormField label="NIP" v-model="crud.form.nip" :error="crud.form.errors.nip" />
                <FormField label="Nama guru BK" v-model="crud.form.name" :error="crud.form.errors.name" />
                <FormField label="Username" v-model="crud.form.username" :error="crud.form.errors.username" />
                <FormField :label="crud.editId.value ? 'Password baru (kosongkan jika tidak diubah)' : 'Password'"
                    type="password" v-model="crud.form.password" :error="crud.form.errors.password" />
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="crud.close" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                    <button type="submit" :disabled="crud.form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </Modal>

        <ConfirmModal v-if="crud.deleting.value" title="Hapus guru BK" :processing="crud.busy.value"
            :error="crud.deleteError.value" @close="crud.cancelDelete" @confirm="crud.confirmDelete">
            Hapus guru BK <strong>{{ crud.deleting.value.name }}</strong>? Akunnya tidak bisa dipakai login lagi.
        </ConfirmModal>
    </AppLayout>
</template>