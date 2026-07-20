<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const page = usePage<SharedData>();
const notifications = computed(() => page.props.notifications ?? { unreadCount: 0 });
const role = computed(() => page.props.auth?.user?.role ?? '');
const showNotifications = computed(() => role.value === 'super_admin' || role.value === 'dept_manager');
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
            <Link
                v-if="showNotifications"
                :href="route('feedback.index')"
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
            </Link>
            <SidebarTrigger class="-me-1 text-sidebar-foreground hover:text-primary" />
        </div>
    </header>
</template>
