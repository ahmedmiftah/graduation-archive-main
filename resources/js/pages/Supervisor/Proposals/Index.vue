<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { proposalStatusColor, proposalStatusLabel } from '@/lib/proposalStatusBadge';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface ProposalRow {
    id: number;
    title: string;
    status: string;
    students: string[];
    current_stage: string | null;
    needs_action: boolean;
    updated_at: string;
}

defineProps<{ proposals: ProposalRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/supervisor/dashboard' },
    { title: 'مشاريعي', href: '/supervisor/proposals' },
];
</script>

<template>
    <Head title="مشاريعي" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">مشاريعي</h1>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">اسم المشروع</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الطلاب</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الحالة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">المرحلة الحالية</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">آخر تحديث</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr v-for="p in proposals" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ p.title }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ p.students.join('، ') || '—' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', proposalStatusColor(p.status)]">
                                    {{ proposalStatusLabel(p.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ p.current_stage ?? '—' }}
                                <span
                                    v-if="p.needs_action"
                                    class="mr-1 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-400"
                                >
                                    يتطلب إجراءً
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ new Date(p.updated_at).toLocaleDateString('ar-EG') }}</td>
                            <td class="px-4 py-3">
                                <Link
                                    :href="route('supervisor.proposals.show', p.id)"
                                    class="rounded bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-200 dark:bg-blue-900/20 dark:text-blue-400"
                                >
                                    عرض
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="proposals.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">لا توجد مشاريع مسندة إليك بعد</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
