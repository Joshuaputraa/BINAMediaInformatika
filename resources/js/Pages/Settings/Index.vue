<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import PageHeader from '../../Components/PageHeader.vue'
import Modal from '../../Components/Modal.vue'
import ConfirmModal from '../../Components/ConfirmModal.vue'
import ActionMenu from '../../Components/ActionMenu.vue'
import FormField from '../../Components/FormField.vue'
import { useCrud } from '../../composables/useCrud'

const props = defineProps({ me: Object, accounts: Array })

const profile = useForm({ name: props.me.name, username: props.me.username })
const saveProfile = () => profile.put('/settings/profile', { preserveScroll: true })

const pwd = useForm({ current_password: '', password: '', password_confirmation: '' })
const savePassword = () =>
    pwd.put('/settings/password', { preserveScroll: true, onSuccess: () => pwd.reset() })

const crud = useCrud('/kesiswaan-users', { name: '', username: '', password: '' })
</script>

<template>
    <AppLayout>
        <PageHeader title="Pengaturan" subtitle="Kelola akun Anda dan akun kesiswaan lainnya." />

        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <form @submit.prevent="saveProfile" class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
                <div>
                    <p class="font-medium text-gray-900">Profil saya</p>
                    <p class="text-sm text-gray-500">Nama dan username untuk login.</p>
                </div>
                <FormField label="Nama" required v-model="profile.name" :error="profile.errors.name" />
                <FormField label="Username" required v-model="profile.username" :error="profile.errors.username" />
                <button type="submit" :disabled="profile.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Simpan profil</button>
            </form>

            <form @submit.prevent="savePassword" class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
                <div>
                    <p class="font-medium text-gray-900">Ubah password</p>
                    <p class="text-sm text-gray-500">Masukkan password saat ini untuk konfirmasi.</p>
                </div>
                <FormField label="Password saat ini" type="password" required v-model="pwd.current_password"
                    :error="pwd.errors.current_password" />
                <FormField label="Password baru" type="password" required v-model="pwd.password"
                    :error="pwd.errors.password" />
                <FormField label="Konfirmasi password baru" type="password" required v-model="pwd.password_confirmation" />
                <button type="submit" :disabled="pwd.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Ubah password</button>
            </form>
        </div>

        <PageHeader title="Akun kesiswaan" subtitle="Akun yang bisa mengelola data dan penanganan siswa.">
            <button @click="crud.openAdd" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">
                Tambah kesiswaan
            </button>
        </PageHeader>

        <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-2 font-medium">Nama</th>
                        <th class="px-4 py-2 font-medium">Username</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in accounts" :key="a.id" class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3">
                            {{ a.name }}
                            <span v-if="a.id === me.id" class="ml-2 text-xs text-blue-700 bg-blue-50 rounded px-2 py-0.5">Anda</span>
                        </td>
                        <td class="px-4 py-3">{{ a.username }}</td>
                        <td class="px-4 py-3 text-right">
                            <ActionMenu :can-delete="a.id !== me.id" @edit="crud.openEdit(a)" @delete="crud.askDelete(a)" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Modal v-if="crud.show.value" :title="crud.editId.value ? 'Edit akun kesiswaan' : 'Tambah akun kesiswaan'" @close="crud.close">
            <form @submit.prevent="crud.submit" class="space-y-4">
                <FormField label="Nama" required v-model="crud.form.name" :error="crud.form.errors.name" />
                <FormField label="Username" required v-model="crud.form.username" :error="crud.form.errors.username" />
                <FormField :label="crud.editId.value ? 'Password baru (kosongkan jika tidak diubah)' : 'Password'"
                    type="password" :required="!crud.editId.value" v-model="crud.form.password"
                    :error="crud.form.errors.password" />
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="crud.close" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                    <button type="submit" :disabled="crud.form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </Modal>

        <ConfirmModal v-if="crud.deleting.value" title="Hapus akun kesiswaan" :processing="crud.busy.value"
            :error="crud.deleteError.value" @close="crud.cancelDelete" @confirm="crud.confirmDelete">
            Hapus akun <strong>{{ crud.deleting.value.name }}</strong>? Akun ini tidak bisa dipakai login lagi.
        </ConfirmModal>
    </AppLayout>
</template>