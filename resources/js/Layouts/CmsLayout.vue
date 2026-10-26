<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { index as recurringRemindersIndex } from '@/routes/cms/recurring';
import { useTranslation } from '@/Composables/useTranslation';
import {
    ChartNoAxesCombined,
    Bell,
    Menu,
    LayoutDashboard,
    LogOut,
    MessageCircle,
    Package,
    PanelLeftClose,
    PanelLeftOpen,
    Settings,
    Tags,
    Wallet,
    X,
} from 'lucide-vue-next';

const page = usePage();
const { t } = useTranslation();
const user = computed(() => page.props.auth?.user);
const currentPath = computed(() => page.url.split('?')[0]);
const isSidebarCollapsed = ref(false);
const isMobileSidebarOpen = ref(false);

const closeMobileSidebar = () => {
    isMobileSidebarOpen.value = false;
};

const navigation = [
    { label: 'Dashboard', href: '/cms/dashboard', icon: LayoutDashboard },
    { label: 'Transactions', href: '/cms/transactions', icon: ChartNoAxesCombined },
    { label: 'Accounts', href: '/cms/accounts', icon: Wallet },
    { label: 'Assets', href: '/cms/assets', icon: Package },
    { label: 'Categories', href: '/cms/categories', icon: Tags },
    { label: 'Recurring reminders', href: recurringRemindersIndex.url(), icon: Bell },
    { label: 'AI Assistant', href: '/cms/chat', icon: MessageCircle },
];
</script>

<template>
    <div class="cms-layout min-h-screen bg-[#F5F7FB] text-[#152338]">
        <Head>
            <meta name="viewport" content="width=device-width, initial-scale=1" />
        </Head>

        <template v-if="user">
            <aside
                class="cms-sidebar fixed inset-y-0 left-0 z-40 hidden w-[252px] flex-col border-r border-[#E7EBF2] bg-white px-5 py-7 shadow-none transition-[width,transform] duration-300"
                :class="[
                    isMobileSidebarOpen ? 'flex translate-x-0' : 'lg:flex lg:translate-x-0',
                    isSidebarCollapsed ? 'lg:w-20 lg:px-3' : 'lg:w-[252px]',
                ]"
            >
                <Link
                    href="/cms/dashboard"
                    class="flex items-center gap-3 px-2"
                    :class="isSidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                    :aria-label="isSidebarCollapsed ? 'Clarity home' : undefined"
                    :title="isSidebarCollapsed ? 'Clarity' : undefined"
                >
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#2457DA] text-xl font-bold text-white">c</span>
                    <span v-if="!isSidebarCollapsed" class="text-2xl font-semibold tracking-tight">clarity</span>
                </Link>
                <button
                    type="button"
                    class="absolute right-4 top-7 flex h-10 w-10 items-center justify-center rounded-xl text-[#637083] hover:bg-[#F1F4F9] hover:text-[#2457DA] lg:hidden"
                    :aria-label="t('Close navigation')"
                    @click="closeMobileSidebar"
                >
                    <X :size="20" />
                </button>

                <p v-if="!isSidebarCollapsed" class="mb-3 mt-10 px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-[#98A2B3]">{{ t('Workspace') }}</p>
                <div v-else class="mb-3 mt-10 hidden h-3 lg:block" />
                <nav class="flex flex-col gap-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition"
                        :class="[
                            currentPath === item.href || currentPath.startsWith(`${item.href}/`)
                                ? 'bg-[#EEF3FF] text-[#2457DA]'
                                : 'text-[#637083] hover:bg-[#F5F7FA] hover:text-[#152338]',
                            isSidebarCollapsed ? 'lg:justify-center lg:px-0' : '',
                        ]"
                        :aria-label="isSidebarCollapsed ? t(item.label) : undefined"
                        :title="isSidebarCollapsed ? t(item.label) : undefined"
                        @click="closeMobileSidebar"
                    >
                        <component :is="item.icon" :size="19" />
                        <span v-if="!isSidebarCollapsed">{{ t(item.label) }}</span>
                    </Link>
                </nav>

                <div class="mt-auto border-t border-[#E9EDF3] pt-5">
                    <Link
                        href="/cms/profile"
                        class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-[#637083] hover:bg-[#F5F7FA]"
                        :class="isSidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                        :aria-label="isSidebarCollapsed ? t('Settings') : undefined"
                        :title="isSidebarCollapsed ? t('Settings') : undefined"
                    >
                        <Settings :size="19" />
                        <span v-if="!isSidebarCollapsed">{{ t('Settings') }}</span>
                    </Link>
                    <Link
                        href="/cms/logout"
                        method="post"
                        as="button"
                        class="mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-medium text-[#637083] hover:bg-[#FFF2F0] hover:text-[#B34735]"
                        :class="isSidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                        :aria-label="isSidebarCollapsed ? t('Log Out') : undefined"
                        :title="isSidebarCollapsed ? t('Log Out') : undefined"
                    >
                        <LogOut :size="19" />
                        <span v-if="!isSidebarCollapsed">{{ t('Log Out') }}</span>
                    </Link>
                </div>
            </aside>

            <button
                v-if="isMobileSidebarOpen"
                type="button"
                class="fixed inset-0 z-10 bg-[#152338]/40 lg:hidden"
                aria-label="Close navigation"
                @click="closeMobileSidebar"
            />

            <div
                class="cms-main"
                :class="[
                    currentPath === '/cms/chat'
                        ? 'flex h-dvh flex-col overflow-hidden'
                        : 'min-h-screen',
                    isSidebarCollapsed ? 'lg:pl-20' : 'lg:pl-[252px]',
                ]"
            >
                <header class="cms-topbar sticky top-0 z-20 flex h-[76px] items-center justify-between border-b border-[#E7EBF2] bg-white px-5 shadow-none sm:px-8 lg:px-10">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-xl text-[#637083] transition hover:bg-[#F1F4F9] hover:text-[#2457DA] lg:hidden"
                            :aria-expanded="isMobileSidebarOpen"
                            :aria-label="t('Toggle navigation')"
                            @click="isMobileSidebarOpen = !isMobileSidebarOpen"
                        >
                            <X v-if="isMobileSidebarOpen" :size="20" />
                            <Menu v-else :size="20" />
                        </button>
                        <button
                            type="button"
                            class="hidden h-10 w-10 items-center justify-center rounded-xl text-[#637083] transition hover:bg-[#F1F4F9] hover:text-[#2457DA] lg:flex"
                            :aria-expanded="!isSidebarCollapsed"
                            :aria-label="t(isSidebarCollapsed ? 'Show navigation' : 'Hide navigation')"
                            @click="isSidebarCollapsed = !isSidebarCollapsed"
                        >
                            <PanelLeftOpen v-if="isSidebarCollapsed" :size="20" />
                            <PanelLeftClose v-else :size="20" />
                        </button>
                        <div>
                            <p class="text-xs text-[#98A2B3]">{{ t('Personal finance workspace') }}</p>
                            <p class="mt-1 text-sm font-semibold">{{ user.name }}</p>
                        </div>
                    </div>
                    <Link href="/cms/profile" class="flex h-10 w-10 items-center justify-center rounded-full bg-[#EEF3FF] text-sm font-semibold text-[#2457DA]" :aria-label="`Open ${user.name}'s profile`">
                        {{ user.name.slice(0, 2).toUpperCase() }}
                    </Link>
                </header>

                <main
                    class="cms-content w-full"
                    :class="currentPath === '/cms/chat'
                        ? 'cms-content--chat flex min-h-0 flex-1'
                        : 'mx-auto max-w-[1440px] px-5 py-7 sm:px-8 lg:px-10 lg:py-9'"
                >
                    <slot />
                </main>
            </div>
        </template>

        <main v-else class="cms-guest min-h-screen">
            <slot />
        </main>
    </div>
</template>

<style>
.cms-layout [class*="shadow-"],
.cms-layout .button,
.cms-layout .button-primary,
.cms-layout .cms-login-card,
.cms-modal [class*="shadow-"],
.cms-modal .button,
.cms-modal .button-primary {
    box-shadow: none !important;
}

.cms-layout .max-w-lg {
    max-width: 100%;
}

.cms-layout .fixed.inset-x-0.bottom-0 {
    display: none;
}

.cms-layout .fixed.inset-0.z-30 {
    align-items: center;
}

.cms-layout .fixed.inset-0.z-30 > form {
    width: min(100% - 3rem, 640px);
    margin-inline: auto;
    border-radius: 24px;
}

.cms-layout .cms-guest > main {
    max-width: 1120px;
    margin-inline: auto;
}

.cms-layout .cms-content.cms-content--chat {
    padding: 0;
}

@media (max-width: 1023px) {
    .cms-layout .cms-content {
        padding-bottom: 2rem;
    }
}
</style>
