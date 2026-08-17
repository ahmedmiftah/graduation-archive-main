<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const page = usePage<SharedData>();
const notifications = computed(() => page.props.notifications ?? { unreadCount: 0, recent: [] });

function timeAgo(value: string): string {
    const diffMs = Date.now() - new Date(value).getTime();
    const minutes = Math.floor(diffMs / 60000);
    if (minutes < 1) return 'الآن';
    if (minutes < 60) return `منذ ${minutes} د`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `منذ ${hours} س`;
    const days = Math.floor(hours / 24);
    return `منذ ${days} يوم`;
}

function openNotification(notification: (typeof notifications.value.recent)[number]) {
    if (!notification.read_at) {
        router.patch(route('notifications.read', notification.id), {}, { preserveScroll: true });
    }
    if (notification.url) {
        router.visit(notification.url);
    }
}

function markAllAsRead() {
    router.patch(route('notifications.read-all'), {}, { preserveScroll: true });
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-4 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12"
    >
        <!-- Breadcrumbs on the start (left) -->
        <div class="flex items-center gap-2">
            <template v-if="props.breadcrumbs && props.breadcrumbs.length > 0">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in props.breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <template v-if="index === props.breadcrumbs.length - 1">
                                    <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                                </template>
                                <template v-else>
                                    <BreadcrumbLink :href="item.href">
                                        {{ item.title }}
                                    </BreadcrumbLink>
                                </template>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== props.breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </template>
        </div>

        <div class="flex items-center gap-2">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-transparent bg-surface text-text-dark hover:border-primary/30 hover:text-primary"
                        aria-label="الإشعارات"
                    >
                        <Bell class="h-5 w-5" />
                        <span
                            v-if="notifications.unreadCount > 0"
                            class="absolute -end-1 -top-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[0.65rem] font-semibold text-white"
                        >
                            {{ notifications.unreadCount }}
                        </span>
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-80 rounded-lg p-0" side="bottom" align="end" :side-offset="4">
                    <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2">
                        <span class="text-sm font-semibold text-text-dark">الإشعارات</span>
                        <button
                            v-if="notifications.unreadCount > 0"
                            type="button"
                            class="text-xs font-medium text-primary hover:underline"
                            @click="markAllAsRead"
                        >
                            تعليم الكل كمقروء
                        </button>
                    </div>

                    <div v-if="notifications.recent.length === 0" class="px-3 py-6 text-center text-sm text-slate-400">
                        لا توجد إشعارات
                    </div>

                    <ul v-else class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
                        <li v-for="n in notifications.recent" :key="n.id">
                            <button
                                type="button"
                                class="flex w-full flex-col items-start gap-0.5 px-3 py-2.5 text-start hover:bg-slate-50"
                                :class="{ 'bg-primary/5': !n.read_at }"
                                @click="openNotification(n)"
                            >
                                <span class="flex w-full items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-text-dark">{{ n.title }}</span>
                                    <span v-if="!n.read_at" class="h-2 w-2 shrink-0 rounded-full bg-primary" />
                                </span>
                                <span class="line-clamp-2 text-xs text-slate-500">{{ n.message }}</span>
                                <span class="text-[0.7rem] text-slate-400">{{ timeAgo(n.created_at) }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="border-t border-slate-100 px-3 py-2 text-center">
                        <Link :href="route('notifications.index')" class="text-xs font-medium text-primary hover:underline">
                            عرض كل الإشعارات
                        </Link>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>
            <SidebarTrigger class="-me-1 text-sidebar-foreground hover:text-primary" />
        </div>
    </header>
</template>
