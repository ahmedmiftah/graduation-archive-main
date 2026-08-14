<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';

interface NavItem {
    title: string;
    href: string;
    icon: Component;
}

defineProps<{
    items: NavItem[];
}>();

const page = usePage<SharedData>();

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
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
