<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CheckCircle2, ClipboardList, FileWarning, FolderOpen, Gavel, Loader2, UserCheck } from 'lucide-vue-next';
import { computed } from 'vue';

interface Stats {
    total_projects: number;
    in_progress_count: number;
    completed_count: number;
    ready_for_defense_count: number;
    needs_action_count: number;
    pending_documentation_review_count: number;
    examiner_assignments_count: number;
}

interface FacultyMemberInfo {
    id: number;
    full_name: string;
    email: string;
}

defineProps<{
    facultyMember: FacultyMemberInfo;
    stats: Stats;
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs: BreadcrumbItem[] = [{ title: 'لوحة التحكم', href: '/supervisor/dashboard' }];
</script>

<template>
    <Head title="لوحة التحكم" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4" dir="rtl">
            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>

            <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">مرحباً {{ facultyMember.full_name }}</h1>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <FolderOpen class="mb-2 h-5 w-5 text-blue-600" />
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total_projects }}</div>
                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">إجمالي المشاريع</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <Loader2 class="mb-2 h-5 w-5 text-amber-600" />
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.in_progress_count }}</div>
                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">قيد التنفيذ</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <CheckCircle2 class="mb-2 h-5 w-5 text-green-600" />
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.completed_count }}</div>
                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">منجزة</div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-5 dark:border-red-900/40 dark:bg-red-900/10">
                    <FileWarning class="mb-2 h-5 w-5 text-red-600" />
                    <div class="text-2xl font-bold text-red-700 dark:text-red-400">{{ stats.needs_action_count }}</div>
                    <div class="mt-0.5 text-xs text-red-600 dark:text-red-400">تحتاج إجراءً منك</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <ClipboardList class="mb-2 h-5 w-5 text-indigo-600" />
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.pending_documentation_review_count }}</div>
                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">توثيقات تحتاج مراجعة</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <Gavel class="mb-2 h-5 w-5 text-purple-600" />
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.ready_for_defense_count }}</div>
                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">جاهزة للمناقشة</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <UserCheck class="mb-2 h-5 w-5 text-teal-600" />
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.examiner_assignments_count }}</div>
                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">تكليفات كممتحن</div>
                </div>
            </div>

            <Link
                :href="route('supervisor.proposals.index')"
                class="inline-flex w-fit items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700"
            >
                عرض مشاريعي
            </Link>
        </div>
    </AppLayout>
</template>
