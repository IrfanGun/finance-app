<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowLeft,
    Bell,
    ChevronRight,
    CircleHelp,
    LockKeyhole,
    LogOut,
    ShieldCheck,
    UserRound,
} from 'lucide-vue-next';
import { dashboard, logout } from '@/routes';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const activePanel = ref(null);
const user = computed(() => page.props.auth.user);
const initials = computed(() => user.value.name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase());
const detailTitle = computed(() => activePanel.value === 'security'
    ? 'Security'
    : 'Edit Profile');

const accountItems = [
    {
        id: 'profile',
        title: 'Personal information',
        description: 'Manage your account details',
        icon: UserRound,
    },
    {
        id: 'security',
        title: 'Security',
        description: 'Keep your account protected',
        icon: ShieldCheck,
    },
];

const generalItems = [
    {
        title: 'Notifications',
        description: 'Manage your notification preferences',
        icon: Bell,
        label: 'Soon',
    },
    {
        title: 'Application status',
        description: 'Everything is running normally',
        icon: Activity,
        label: 'Online',
    },
    {
        title: 'Help center',
        description: 'Find answers and get support',
        icon: CircleHelp,
    },
];

const openPanel = (panel) => {
    activePanel.value = panel;
};

const closePanel = () => {
    activePanel.value = null;
};
</script>

<template>
    <Head :title="activePanel ? detailTitle : 'Settings'" />

    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <header class="flex items-center justify-between px-6 py-5">
            <Link
                v-if="!activePanel"
                :href="dashboard.url()"
                class="rounded-full p-1 text-app-primary transition hover:bg-blue-50"
                aria-label="Back to dashboard"
            >
                <ArrowLeft :size="22" />
            </Link>
            <button
                v-else
                type="button"
                class="rounded-full p-1 text-app-primary transition hover:bg-blue-50"
                aria-label="Back to settings"
                @click="closePanel"
            >
                <ArrowLeft :size="22" />
            </button>
            <h1 class="text-lg font-semibold">
                {{ activePanel ? detailTitle : 'Settings' }}
            </h1>
            <span class="w-7" aria-hidden="true"></span>
        </header>

        <main v-if="!activePanel" class="space-y-7 px-6 pt-1">
            <button
                type="button"
                class="flex w-full items-center gap-4 rounded-xl bg-app-card px-4 py-3 text-left transition hover:bg-slate-100"
                @click="openPanel('profile')"
            >
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-app-primary/10 text-lg font-semibold text-app-primary">
                    {{ initials }}
                </span>
                <span class="min-w-0 flex-1">
                    <strong class="block truncate text-sm font-semibold">{{ user.name }}</strong>
                    <span class="mt-1 block text-xs text-app-muted">View profile</span>
                </span>
                <ChevronRight :size="18" class="shrink-0 text-app-muted" />
            </button>

            <section class="space-y-3">
                <h2 class="text-base font-semibold">Account</h2>

                <div class="divide-y divide-white/80 overflow-hidden rounded-xl bg-app-card">
                    <button
                        v-for="item in accountItems"
                        :key="item.id"
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-3.5 text-left transition hover:bg-slate-100"
                        @click="openPanel(item.id)"
                    >
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-app-primary">
                            <component :is="item.icon" :size="18" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <strong class="block text-sm font-medium">{{ item.title }}</strong>
                            <span class="mt-0.5 block text-xs text-app-muted">{{ item.description }}</span>
                        </span>
                        <ChevronRight :size="18" class="shrink-0 text-app-muted" />
                    </button>
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="text-base font-semibold">General</h2>

                <div class="divide-y divide-white/80 overflow-hidden rounded-xl bg-app-card">
                    <div
                        v-for="item in generalItems"
                        :key="item.title"
                        class="flex items-center gap-3 px-4 py-3.5"
                    >
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-app-primary">
                            <component :is="item.icon" :size="18" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <strong class="block text-sm font-medium">{{ item.title }}</strong>
                            <span class="mt-0.5 block text-xs text-app-muted">{{ item.description }}</span>
                        </span>
                        <span
                            v-if="item.label"
                            class="rounded-full px-2 py-1 text-[10px] font-semibold"
                            :class="item.label === 'Online' ? 'bg-emerald-100 text-emerald-700' : 'bg-app-primary/10 text-app-primary'"
                        >
                            {{ item.label }}
                        </span>
                        <ChevronRight v-else :size="18" class="shrink-0 text-app-muted" />
                    </div>
                </div>
            </section>

            <Link
                :href="logout.url()"
                method="post"
                as="button"
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-app-divider py-3 text-lg font-semibold text-app-muted transition hover:border-red-200 hover:text-red-600"
            >
                <LogOut :size="18" />
                Log out
            </Link>
        </main>

        <main
            v-else-if="activePanel === 'profile'"
            class="space-y-6 px-6 pt-2"
        >
            <section class="flex flex-col items-center border-b border-app-divider pb-6 text-center">
                <span class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-app-primary/20 bg-app-primary/10 text-2xl font-semibold text-app-primary">
                    {{ initials }}
                </span>
                <h2 class="mt-4 text-xl font-semibold">{{ user.name }}</h2>
                <p class="mt-1 text-base text-app-muted">{{ user.email }}</p>
            </section>

            <UpdateProfileInformationForm
                id="profile-form"
                :must-verify-email="props.mustVerifyEmail"
                :status="props.status"
            />
        </main>

        <main v-else class="space-y-5 px-6 pt-2">
            <section class="rounded-xl bg-app-card p-4">
                <UpdatePasswordForm />
            </section>

            <section class="rounded-xl bg-red-50/40 p-4">
                <DeleteUserForm />
            </section>
        </main>

        <MobileBottomNav />
    </div>
</template>
