<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface Specialization {
    id: number;
    name: string;
}
interface FacultyMember {
    id: number;
    full_name: string;
}
interface Idea {
    id: number;
    title: string;
    specialization: Specialization | null;
    facultyMember: FacultyMember | null;
}
interface IdeaRequest {
    id: number;
    status: 'pending' | 'accepted' | 'rejected';
    message: string | null;
    created_at: string;
    idea: Idea;
}

const props = defineProps<{
    requests: IdeaRequest[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/student/dashboard' },
    { title: 'أفكار المشاريع', href: '/student/ideas' },
    { title: 'طلباتي', href: '/student/idea-requests' },
];

const STATUS_LABELS: Record<string, string> = { pending: 'قيد المراجعة', accepted: 'مقبول', rejected: 'مرفوض' };
const STATUS_COLORS: Record<string, string> = {
    pending: 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
    accepted: 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400',
};
</script>

<template>
    <Head title="طلباتي" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">طلبات الانضمام التي أرسلتها</h1>

            <div v-if="props.requests.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">لم ترسل أي طلب انضمام بعد.</p>
                <Link :href="route('student.ideas.index')" class="mt-3 inline-block text-sm font-medium text-blue-600 hover:underline">
                    تصفّح أفكار المشاريع
                </Link>
            </div>

            <div v-else class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الفكرة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">المشرف</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الحالة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">تاريخ الطلب</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr v-for="r in props.requests" :key="r.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-sm">
                                <Link :href="route('student.ideas.show', r.idea.id)" class="font-medium text-blue-600 hover:underline">{{ r.idea.title }}</Link>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ r.idea.facultyMember?.full_name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', STATUS_COLORS[r.status]]">{{ STATUS_LABELS[r.status] }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ r.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
