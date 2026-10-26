<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { Download } from 'lucide-vue-next'
import AppLayout from '../../Layouts/AppLayout.vue'
import PageHeader from '../../Components/PageHeader.vue'

defineProps({ nisDigits: Number, maxRows: Number })

const page = usePage()
const result = computed(() => page.props.flash.import_result)

const fileInput = ref(null)
const form = useForm({ file: null, allow_create: false, dry_run: false })

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
</script>

<template>
    <AppLayout>
        <PageHeader title="Update Massal" subtitle="Perbarui kelas, jurusan, guru BK, dan wali kelas banyak siswa sekaligus, misalnya saat kenaikan kelas.">
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

                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input v-model="form.allow_create" type="checkbox" class="mt-0.5 rounded border-gray-300" />
                    <span>Tambahkan siswa baru jika NIS belum terdaftar</span>
                </label>

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
                    <li>Unduh template, lalu isi sheet <strong>Template</strong> mulai baris 2. Arahkan kursor ke judul kolom untuk melihat note.</li>
                    <li>NIS berisi <strong>{{ nisDigits }} digit angka</strong>. Kelas dan Jurusan dipilih dari dropdown.</li>
                    <li>NIP Guru BK harus guru BK dari kelas yang dipilih. Daftar lengkap ada di sheet <strong>Referensi</strong>.</li>
                    <li>Gunakan <strong>Periksa file</strong> untuk mengecek isi file tanpa menyimpan apa pun.</li>
                    <li>Jika ada satu saja baris yang salah, <strong>seluruh impor dibatalkan</strong> dan kesalahannya ditampilkan di bawah.</li>
                    <li>Maksimal {{ maxRows }} baris per file.</li>
                </ol>
            </div>
        </div>

        <!-- Hasil -->
        <div v-if="result" class="mt-6">
            <div v-if="result.ok" class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 text-sm">
                <p class="font-medium">{{ result.dry_run ? 'File valid dan siap diimpor.' : 'Impor berhasil.' }}</p>
                <p class="mt-1">
                    {{ result.updated }} siswa {{ result.dry_run ? 'akan diperbarui' : 'diperbarui' }},
                    {{ result.created }} siswa {{ result.dry_run ? 'akan ditambahkan' : 'ditambahkan' }}.
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