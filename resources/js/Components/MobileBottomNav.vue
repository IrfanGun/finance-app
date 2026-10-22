<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { CircleUserRound, Home, MessageCircle, Package, Plus } from 'lucide-vue-next';
import { chat as chatIndex } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { index as assetsIndex } from '@/routes/assets';
const emit = defineEmits(['add']);

const page = usePage();
const isActive = (path) => page.url === path;
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-20 mx-auto flex h-[84px] max-w-lg items-center justify-around border-t border-[#E9EDF3] bg-white/95 px-4 pb-2 pt-3 text-[11px] text-[#637083] shadow-[0_-4px_18px_rgba(21,35,56,0.05)] backdrop-blur-sm">
        <Link href="/dashboard" class="nav-item" :class="{ 'nav-active': isActive('/dashboard') }" aria-label="Home"><Home class="nav-icon" :size="21" /><span class="hidden sm:inline">Home</span></Link>
        <Link
            :href="assetsIndex.url()"
            class="nav-item"
            :class="{ 'nav-active': isActive(assetsIndex.url()) }"
            aria-label="Assets"
        >
            <Package class="nav-icon" :size="21" />
            <span class="hidden sm:inline">Asset</span>
        </Link>
        <button @click="emit('add')" class="group -mt-6 flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#2457DA] text-white shadow-lg shadow-blue-600/30 transition duration-300 hover:scale-105 active:scale-90 focus:outline-none focus:ring-4 focus:ring-blue-200" aria-label="Add transaction"><Plus class="transition duration-300 group-hover:rotate-90" :size="28" /></button>
        <Link :href="chatIndex.url()" class="nav-item" :class="{ 'nav-active': isActive(chatIndex.url()) }" aria-label="Chat"><MessageCircle class="nav-icon" :size="21" /><span class="hidden sm:inline">Chat</span></Link>
        <Link :href="profileEdit.url()" class="nav-item" :class="{ 'nav-active': isActive(profileEdit.url()) }" aria-label="Profile"><CircleUserRound class="nav-icon" :size="21" /><span class="hidden sm:inline">Profile</span></Link>
    </nav>
</template>

<style scoped>
.nav-item { @apply flex min-w-0 flex-1 flex-col items-center gap-1 transition duration-200 hover:-translate-y-0.5 hover:text-[#2457DA] active:scale-90; }
.nav-active { @apply font-medium text-[#2457DA]; }
</style>
