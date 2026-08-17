<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, type Component } from 'vue';

interface NavItem {
    title: string;
    href: string;
    icon: Component;
}

defineProps<{
    items: NavItem[];
}>();

const page = usePage<SharedData>();
const unreadCount = computed(() => page.props.notifications?.unreadCount ?? 0);

// A project details page reached from the archive list (?from=archived) should
// highlight "أرشيف المشاريع" instead of the generic "المشاريع" item, since both
// hrefs start with /projects.
const cameFromArchive = new URLSearchParams(window.location.search).get('from') === 'archived';

function isActive(href: string): boolean {
    if (href === '/') return false;
    if (cameFromArchive) {
        if (href === '/projects/archived') return true;
        if (href === '/projects') return false;
    }
    return page.url.startsWith(href);
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>القائمة</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton as-child :is-active="isActive(item.href)">
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span class="truncate">{{ item.title }}</span>
                        <span
                            v-if="item.href === '/notifications' && unreadCount > 0"
                            class="ms-auto inline-flex h-5 min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-red-500 px-1.5 text-[0.65rem] font-semibold text-white"
                        >
                            {{ unreadCount }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
