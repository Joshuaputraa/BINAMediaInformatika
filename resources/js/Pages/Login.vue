<script setup>
import { useForm } from '@inertiajs/vue3'
import FormField from '../Components/FormField.vue'

const form = useForm({ username: '', password: '' })
const submit = () => form.post('/login')
</script>

<template>
    <div class="min-h-screen grid md:grid-cols-2">
        <div class="hidden md:flex flex-col justify-between bg-blue-800 text-white p-12">
            <div class="flex items-center gap-2">
                <img src="/images/metiklogo.png" alt="Logo Bina Media Informatika" class="w-10 h-10 object-contain"/>
                <span class="font-semibold">BINA Media Informatika</span>
            </div>
            <div class="pt-10">
                <h1 class="text-3xl font-semibold leading-snug">Setiap Proses Adalah Ruang Untuk Tumbuh.</h1>
                <p class="mt-4 text-sm text-blue-200 max-w-sm">
                    Kelola catatan, pendampingan, dan perkembangan siswa dalam satu ruang.
                </p>
                <img src="/images/students2.jpg" alt="Logo Bina Media Informatika" class="w-[550px] h-[450px] object-contain mt-0"/>
            </div>
            <p class="text-xs text-blue-300">Program Pengabdian Kepada Masyarakat - Teknik Informatika | Universitas Pamulang</p>
        </div>

        <div class="flex items-center justify-center px-6 py-12 bg-white">
            <form @submit.prevent="submit" class="w-full max-w-sm space-y-5">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">Masuk</h2>
                    <p class="text-sm text-gray-500 mt-1">Gunakan akun guru BK atau kesiswaan.</p>
                </div>

                <FormField label="Username" v-model="form.username" :error="form.errors.username" />
                <FormField label="Password" type="password" v-model="form.password" :error="form.errors.password" />

                <button type="submit" :disabled="form.processing"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2.5 rounded-md">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</template>