<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Archive,
    BarChart2,
    Bell,
    BookOpen,
    Bookmark,
    Building2,
    ClipboardList,
    FileText,
    FolderOpen,
    GraduationCap,
    LayoutGrid,
    Lightbulb,
    MessageSquare,
    Search,
    Settings,
    Upload,
    UserCheck,
    Users,
    UsersRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import Logo from './Brand/Logo.vue';

const page = usePage<SharedData>();
const role = computed(() => page.props.auth?.user?.role ?? '');

const navByRole = computed(() => {
    switch (role.value) {
        case 'super_admin':
            return [
                { title: 'لوحة التحكم', href: '/dashboard', icon: LayoutGrid },
                { title: 'الأقسام', href: '/departments', icon: Building2 },
                { title: 'المستخدمون', href: '/admin/users', icon: Users },
                { title: 'المشاريع', href: '/projects', icon: FolderOpen },
                { title: 'أرشيف المشاريع', href: '/projects/archived', icon: Archive },
                { title: 'المقترحات', href: '/proposals', icon: FileText },
                { title: 'أرشيف المقترحات', href: '/proposals/archived', icon: Archive },
                { title: 'أعضاء هيئة التدريس', href: '/faculty-members', icon: UserCheck },
                { title: 'الدرجات العلمية', href: '/academic-degrees', icon: GraduationCap },
                { title: 'الطلاب', href: '/students', icon: UsersRound },
                { title: 'استيراد الطلاب', href: '/students/import', icon: Upload },
                { title: 'إعدادات النظام', href: '/settings/system', icon: Settings },
                { title: 'لوحة الملاحظات', href: '/feedback', icon: MessageSquare },
                { title: 'الإشعارات', href: '/notifications', icon: Bell },

                { title: 'التقارير', href: '/reports/department', icon: BarChart2 },
                { title: 'استيراد', href: '/import', icon: Upload },
                { title: 'تصفح المشاريع', href: '/browse', icon: BookOpen },
            ];
        case 'dept_manager':
            return [
                { title: 'لوحة التحكم', href: '/dashboard', icon: LayoutGrid },
                { title: 'المستخدمون', href: '/admin/users', icon: Users },
                { title: 'المشاريع', href: '/projects', icon: FolderOpen },
                { title: 'أرشيف المشاريع', href: '/projects/archived', icon: Archive },
                { title: 'المقترحات', href: '/proposals', icon: FileText },
                { title: 'أرشيف المقترحات', href: '/proposals/archived', icon: Archive },
                { title: 'التخصصات', href: '/departments', icon: Building2 },
                { title: 'أعضاء هيئة التدريس', href: '/faculty-members', icon: UserCheck },
                { title: 'الطلاب', href: '/students', icon: UsersRound },
                { title: 'استيراد الطلاب', href: '/students/import', icon: Upload },
                { title: 'لوحة الملاحظات', href: '/feedback', icon: MessageSquare },
                { title: 'الإشعارات', href: '/notifications', icon: Bell },
                { title: 'البحث', href: '/search', icon: Search },

                { title: 'التقارير', href: '/reports/department', icon: BarChart2 },
                { title: 'تصفح المشاريع', href: '/browse', icon: BookOpen },
            ];
        case 'dept_staff':
            return [
                { title: 'لوحة التحكم', href: '/dashboard', icon: LayoutGrid },
                { title: 'المشاريع', href: '/projects', icon: FolderOpen },
                { title: 'المقترحات', href: '/proposals', icon: FileText },
                { title: 'الإشعارات', href: '/notifications', icon: Bell },

                { title: 'البحث', href: '/search', icon: Search },
                { title: 'تصفح المشاريع', href: '/browse', icon: BookOpen },
            ];
        case 'student':
            return [
                { title: 'لوحة التحكم', href: '/student/dashboard', icon: LayoutGrid },
                { title: 'أفكار المشاريع', href: '/student/ideas', icon: Lightbulb },
                { title: 'طلباتي', href: '/student/idea-requests', icon: ClipboardList },
                { title: 'حجزي', href: '/student/reservation', icon: Bookmark },
                { title: 'الإشعارات', href: '/notifications', icon: Bell },
                { title: 'تصفح المشاريع', href: '/browse', icon: BookOpen },
            ];
        case 'supervisor':
            return [
                { title: 'لوحة التحكم', href: '/supervisor/dashboard', icon: LayoutGrid },
                { title: 'مشاريعي', href: '/supervisor/proposals', icon: FolderOpen },
                { title: 'أفكار المشاريع', href: '/supervisor/ideas', icon: Lightbulb },
                { title: 'الإشعارات', href: '/notifications', icon: Bell },
            ];
        default:
            return [
                { title: 'لوحة التحكم', href: '/dashboard', icon: LayoutGrid },
                { title: 'الإشعارات', href: '/notifications', icon: Bell },
                { title: 'البحث', href: '/search', icon: Search },
                { title: 'تصفح المشاريع', href: '/browse', icon: BookOpen },
            ];
    }
});
</script>

<template>
    <Sidebar side="right" collapsible="icon" variant="inset">
        <SidebarHeader class="border-b border-sidebar-border">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')" class="flex items-center gap-3 px-1 py-2">
                            <Logo size="sm" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="navByRole" />
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
