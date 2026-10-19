<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { LayoutDashboard, GraduationCap, Users, UserCheck, FileText, LogOut, Menu, Settings } from 'lucide-vue-next'
import ConfirmModal from '../Components/ConfirmModal.vue'

const page = usePage()
const user = computed(() => page.props.auth.user)
const open = ref(false)
const showLogout = ref(false)

const menus = computed(() => [
    { href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { href: '/students', label: 'Siswa', icon: GraduationCap },
    ...(user.value.role === 'kesiswaan'
        ? [
              { href: '/teachers', label: 'Wali Kelas', icon: Users },
              { href: '/bk-teachers', label: 'Guru BK', icon: UserCheck },
          ]
        : []),
    { href: '/reports', label: 'Laporan', icon: FileText },
])

const isActive = (href) => page.url.startsWith(href)
const roleLabel = computed(() => (user.value.role === 'bk' ? 'Guru BK' : 'Kesiswaan'))
const logout = () => router.post('/logout')
</script>

<template>
    <div class="min-h-screen md:pl-60">
        <div v-if="open" class="fixed inset-0 bg-black/30 z-20 md:hidden" @click="open = false"></div>

        <aside
            class="fixed inset-y-0 left-0 w-60 bg-blue-800 text-blue-100 flex flex-col z-30 transition-transform md:translate-x-0"
            :class="open ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="h-16 px-5 flex items-center gap-3 border-b border-blue-700">
                <img src="/images/metiklogo.png" alt="Logo Bina Media Informatika" class="w-8 h-9 object-contain"/>
                <span class="font-semibold text-white">BINA Media Informatika</span>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                <Link
                    v-for="m in menus"
                    :key="m.href"
                    :href="m.href"
                    @click="open = false"
                    class="flex items-center gap-3 px-3 py-2 rounded-md"
                    :class="isActive(m.href) ? 'bg-white text-blue-800 font-medium' : 'hover:bg-blue-700'"
                >
                    <component :is="m.icon" class="w-4 h-4" />
                    {{ m.label }}
                </Link>
            </nav>

            <div class="px-5 py-4 border-t border-blue-700 text-sm">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="min-w-0">
                        <p class="text-white truncate">{{ user.name }}</p>
                        <p class="text-xs text-blue-300">{{ roleLabel }}</p>
                    </div>
                    <Link v-if="user.role === 'kesiswaan'" href="/settings" title="Pengaturan" @click="open = false"
                        class="p-1.5 rounded-md"
                        :class="isActive('/settings') ? 'bg-white text-blue-800' : 'text-blue-200 hover:text-white hover:bg-blue-700'">
                        <Settings class="w-4 h-4" />
                    </Link>
                </div>
                <button @click="showLogout = true" class="flex items-center gap-2 text-blue-200 hover:text-white">
                    <LogOut class="w-4 h-4" /> Keluar
                </button>
            </div>
        </aside>

        <header class="md:hidden h-14 bg-white border-b border-gray-200 flex items-center px-4">
            <button @click="open = true" class="text-gray-600"><Menu class="w-5 h-5" /></button>
        </header>

        <main class="max-w-6xl mx-auto px-6 py-8">
            <p
                v-if="page.props.flash.success"
                class="mb-4 text-sm text-blue-700 bg-blue-50 border border-blue-100 px-3 py-2 rounded-md"
            >
                {{ page.props.flash.success }}
            </p>
            <slot />
        </main>
        <ConfirmModal v-if="showLogout" title="Keluar" confirm-label="Keluar" tone="primary"
            @close="showLogout = false" @confirm="logout">
            Yakin ingin keluar dari aplikasi?
        </ConfirmModal>
    </div>
</template>