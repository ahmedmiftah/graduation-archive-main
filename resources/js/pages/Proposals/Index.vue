<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'المقترحات', href: '/proposals' },
];

interface Proposal {
    id: number;
    title: string;
    supervisor: { name: string } | null;
    department: { name: string } | null;
    status: string;
    submission_date: string | null;
    committee_decision: string | null;
    created_at: string;
}

interface Meta {
    current_page: number;
    last_page: number;
}

const proposals = ref<Proposal[]>([]);
const meta = ref<Meta>({ current_page: 1, last_page: 1 });
const searchQuery = ref('');
const filters = ref({
    status: '',
    page: 1,
});

import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => authUser.value?.role === 'super_admin');

const fetchProposals = async () => {
    try {
        const params = {
            ...filters.value,
            search: searchQuery.value,
        };
        const response = await axios.get(route('api.proposals.index'), { params });
        proposals.value = response.data.data;
        meta.value = response.data.meta;
    } catch (error) {
        console.error('Error fetching proposals:', error);
    }
};

const changePage = (page: number) => {
    filters.value.page = page;
    fetchProposals();
};

const deleteProposal = async (id: number) => {
    if (confirm('هل أنت متأكد من حذف هذا المقترح؟')) {
        try {
            await axios.delete(route('api.proposals.destroy', { proposal: id }));
            fetchProposals();
        } catch (error) {
            console.error('Error deleting proposal:', error);
        }
    }
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        new: 'جديد',
        under_review: 'قيد المراجعة',
        approved: 'تمت الموافقة',
        rejected: 'مرفوض',
        archived: 'مؤرشف',
    };
    return labels[status] || status;
};

const getDecisionLabel = (decision: string | null) => {
    if (!decision) return '—';
    const labels: Record<string, string> = {
        accepted: 'مقبول',
        accepted_with_modifications: 'مقبول مع تعديلات',
        rejected: 'مرفوض',
    };
    return labels[decision] || decision;
};

onMounted(() => {
    fetchProposals();
});
</script>

<template>
    <Head title="المقترحات" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-w-0 p-6" dir="rtl">
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-800">مقترحات المشاريع</h1>
                </div>
                <Link
                    :href="route('proposals.create')"
                    class="shrink-0 whitespace-nowrap rounded bg-blue-600 px-4 py-2 text-white shadow hover:bg-blue-700"
                >
                    إنشاء مقترح جديد
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-wrap gap-4 rounded bg-white p-4 shadow">
                <input
                    v-model="searchQuery"
                    placeholder="بحث باسم الطالب أو عنوان المشروع أو اسم المشرف"
                    class="flex-grow rounded border p-2"
                    @input="fetchProposals"
                />
                <select v-model="filters.status" class="rounded border p-2" @change="fetchProposals">
                    <option value="">كل الحالات</option>
                    <option value="new">جديد</option>
                    <option value="under_review">قيد المراجعة</option>
                    <option value="approved">تمت الموافقة</option>
                    <option value="rejected">مرفوض</option>
                    <option value="archived">مؤرشف</option>
                </select>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded bg-white shadow">
                <table class="w-full border-collapse text-right">
                    <thead>
                        <tr class="border-b bg-gray-50">
                            <th class="p-4 font-semibold text-gray-600">عنوان المشروع</th>
                            <th class="p-4 font-semibold text-gray-600" v-if="isSuperAdmin">القسم</th>
                            <th class="p-4 font-semibold text-gray-600">المشرف</th>
                            <th class="p-4 font-semibold text-gray-600">الحالة</th>
                            <th class="p-4 font-semibold text-gray-600">تاريخ التسليم</th>
                            <th class="p-4 font-semibold text-gray-600">القرار</th>
                            <th class="p-4 font-semibold text-gray-600">تاريخ الإنشاء</th>
                            <th class="whitespace-nowrap p-4 font-semibold text-gray-600" style="min-width: 180px">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="proposal in proposals" :key="proposal.id" class="border-b hover:bg-gray-50">
                            <td class="p-4">{{ proposal.title }}</td>
                            <td class="p-4" v-if="isSuperAdmin">{{ proposal.department ? proposal.department.name : 'غير محدد' }}</td>
                            <td class="p-4">{{ proposal.supervisor ? proposal.supervisor.name : 'غير محدد' }}</td>
                            <td class="p-4">
                                <span class="rounded bg-blue-100 px-2 py-1 text-sm text-blue-800">{{ getStatusLabel(proposal.status) }}</span>
                            </td>
                            <td class="p-4">{{ proposal.submission_date || '-' }}</td>
                            <td class="p-4">{{ getDecisionLabel(proposal.committee_decision) }}</td>
                            <td class="p-4">{{ proposal.created_at.split(' ')[0] }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-1">
                                    <Link
                                        :href="route('proposals.show', proposal.id)"
                                        class="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    >
                                        عرض
                                    </Link>
                                    <Link
                                        :href="route('proposals.edit', proposal.id)"
                                        class="rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400"
                                    >
                                        تعديل
                                    </Link>
                                    <button
                                        type="button"
                                        @click="deleteProposal(proposal.id)"
                                        class="rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400"
                                    >
                                        حذف
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="proposals.length === 0">
                            <td :colspan="isSuperAdmin ? 8 : 7" class="p-4 text-center text-gray-500">لا توجد مقترحات</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (Simplified) -->
            <div class="mt-4 flex items-center justify-between" v-if="meta.last_page > 1">
                <button
                    @click="changePage(meta.current_page - 1)"
                    :disabled="meta.current_page === 1"
                    class="rounded border px-4 py-2 disabled:opacity-50"
                >
                    السابق
                </button>
                <span>صفحة {{ meta.current_page }} من {{ meta.last_page }}</span>
                <button
                    @click="changePage(meta.current_page + 1)"
                    :disabled="meta.current_page === meta.last_page"
                    class="rounded border px-4 py-2 disabled:opacity-50"
                >
                    التالي
                </button>
            </div>
        </div>
    </AppLayout>
</template>
