<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import axios from 'axios';

interface Proposal {
    id: number;
    title: string;
    supervisor: { name: string } | null;
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

onMounted(() => {
    fetchProposals();
});
</script>

<template>
    <Head title="المقترحات" />
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center gap-1 text-gray-500 transition hover:text-gray-800"
                    title="العودة للقائمة الرئيسية"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    القائمة الرئيسية
                </Link>
                <span class="text-gray-300">|</span>
                <h1 class="text-2xl font-bold text-gray-800">مقترحات المشاريع</h1>
            </div>
            <Link :href="route('proposals.create')" class="rounded bg-blue-600 px-4 py-2 text-white shadow hover:bg-blue-700">
                إنشاء مقترح جديد
            </Link>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4 rounded bg-white p-4 shadow">
            <input v-model="searchQuery" placeholder="بحث باسم الطالب أو عنوان المشروع أو اسم المشرف" class="flex-grow rounded border p-2" @input="fetchProposals" />
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
                        <td class="p-4">{{ proposal.supervisor ? proposal.supervisor.name : 'غير محدد' }}</td>
                        <td class="p-4">
                            <span class="rounded bg-blue-100 px-2 py-1 text-sm text-blue-800">{{ proposal.status }}</span>
                        </td>
                        <td class="p-4">{{ proposal.submission_date || '-' }}</td>
                        <td class="p-4">{{ proposal.committee_decision || '-' }}</td>
                        <td class="p-4">{{ proposal.created_at.split(' ')[0] }}</td>
                        <td class="p-4">
                            <div class="flex items-center gap-4">
                                <Link
                                    :href="route('proposals.show', proposal.id)"
                                    class="whitespace-nowrap text-sm font-medium text-indigo-600 transition hover:underline"
                                    >عرض</Link
                                >
                                <Link
                                    :href="route('proposals.edit', proposal.id)"
                                    class="whitespace-nowrap text-sm font-medium text-orange-600 transition hover:underline"
                                    >تعديل</Link
                                >
                                <button
                                    @click="deleteProposal(proposal.id)"
                                    class="whitespace-nowrap text-sm font-medium text-red-600 transition hover:underline"
                                >
                                    حذف
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="proposals.length === 0">
                        <td colspan="7" class="p-4 text-center text-gray-500">لا توجد مقترحات</td>
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
</template>