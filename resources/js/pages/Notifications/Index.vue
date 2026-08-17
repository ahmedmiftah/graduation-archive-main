<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { NotificationItem } from '@/types';
import { Link, router } from '@inertiajs/vue3';

interface PaginatedNotifications {
    data: NotificationItem[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    notifications: PaginatedNotifications;
}>();

function openNotification(notification: NotificationItem) {
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
    <AppLayout>
        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">الإشعارات</h1>
                    <p class="mt-1 text-sm text-slate-500">جميع الإشعارات الخاصة بحسابك.</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                    @click="markAllAsRead"
                >
                    تعليم الكل كمقروء
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div v-if="props.notifications.data.length === 0" class="px-4 py-10 text-center text-sm text-slate-400">
                    لا توجد إشعارات بعد
                </div>

                <ul v-else class="divide-y divide-slate-200">
                    <li v-for="n in props.notifications.data" :key="n.id">
                        <button
                            type="button"
                            class="flex w-full items-start justify-between gap-4 px-4 py-3 text-start hover:bg-slate-50"
                            :class="{ 'bg-primary/5': !n.read_at }"
                            @click="openNotification(n)"
                        >
                            <div class="flex items-start gap-3">
                                <span v-if="!n.read_at" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary" />
                                <span v-else class="mt-1.5 h-2 w-2 shrink-0" />
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ n.title }}</p>
                                    <p class="mt-0.5 text-sm text-slate-500">{{ n.message }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 whitespace-nowrap text-xs text-slate-400">{{ n.created_at }}</span>
                        </button>
                    </li>
                </ul>

                <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <span>إجمالي الإشعارات: {{ props.notifications.total }}</span>
                    <div class="space-x-2 rtl:space-x-reverse">
                        <Link
                            v-if="props.notifications.prev_page_url"
                            :href="props.notifications.prev_page_url"
                            class="rounded px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-100"
                            >السابق</Link
                        >
                        <Link
                            v-if="props.notifications.next_page_url"
                            :href="props.notifications.next_page_url"
                            class="rounded px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-100"
                            >التالي</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
