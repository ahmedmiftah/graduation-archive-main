<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { proposalStatusColor, proposalStatusLabel } from '@/lib/proposalStatusBadge';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

interface Department {
    id: number;
    name: string;
}
interface Specialization {
    id: number;
    name: string;
    department_id: number;
}
interface Supervisor {
    id: number;
    full_name: string;
}
interface Proposal {
    id: number;
    title: string;
    status: string;
    academic_year: string;
    semester: string;
    department: Department | null;
    specialization: Specialization | null;
    supervisor: Supervisor | null;
    students_count: number;
    updated_at: string;
}
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
interface PaginatedProposals {
    data: Proposal[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    proposals: PaginatedProposals;
    filterOptions: {
        departments: Department[];
        specializations: Specialization[];
        supervisors: Supervisor[];
    };
    filters: {
        title?: string;
        department_id?: string | number;
        status?: string;
    };
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'المقترحات', href: '/proposals' },
    { title: 'الأرشيف', href: '/proposals/archived' },
];

const search = reactive({
    title: props.filters.title ?? '',
    department_id: props.filters.department_id ?? '',
    status: props.filters.status ?? '',
});

function applyFilters() {
    router.get(route('proposals.archived'), search, { preserveState: true, replace: true });
}

function resetFilters() {
    Object.assign(search, { title: '', department_id: '', status: '' });
    router.get(route('proposals.archived'), {}, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="أرشيف المقترحات" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-w-0 flex-1 flex-col gap-4 p-4" dir="rtl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">أرشيف المقترحات</h1>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-sm font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        {{ proposals.total }}
                    </span>
                </div>
                <a
                    :href="route('proposals.index')"
                    class="shrink-0 whitespace-nowrap rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    المقترحات النشطة
                </a>
            </div>

            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="min-w-[180px] flex-1">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">العنوان</label>
                    <input
                        v-model="search.title"
                        type="text"
                        placeholder="بحث بعنوان المقترح..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <div class="min-w-[160px]">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">القسم</label>
                    <select
                        v-model="search.department_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">الكل</option>
                        <option v-for="d in filterOptions.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>
                <div class="min-w-[160px]">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">الحالة</label>
                    <select
                        v-model="search.status"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">الكل</option>
                        <option value="approved">معتمد</option>
                        <option value="rejected">مرفوض نهائياً</option>
                        <option value="superseded">مستبدَل</option>
                    </select>
                </div>
                <button type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" @click="applyFilters">
                    تصفية
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                    @click="resetFilters"
                >
                    إعادة تعيين
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">العنوان</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">التخصص</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">المشرف</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الفصل الدراسي</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الحالة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr v-for="proposal in proposals.data" :key="proposal.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="max-w-xs px-4 py-3">
                                <a
                                    :href="route('proposals.show', [proposal.id])"
                                    class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                                >
                                    {{ proposal.title }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ proposal.specialization?.name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ proposal.supervisor?.full_name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ proposal.semester }} {{ proposal.academic_year }}
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', proposalStatusColor(proposal.status)]">
                                    {{ proposalStatusLabel(proposal.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a
                                    :href="route('proposals.show', [proposal.id])"
                                    class="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                >
                                    عرض
                                </a>
                            </td>
                        </tr>
                        <tr v-if="proposals.data.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">لا توجد مقترحات في الأرشيف</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="proposals.last_page > 1" class="flex items-center justify-between text-sm">
                <p class="text-gray-600 dark:text-gray-400">صفحة {{ proposals.current_page }} من {{ proposals.last_page }}</p>
                <div class="flex gap-1">
                    <template v-for="link in proposals.links" :key="link.label">
                        <button
                            v-if="link.url"
                            type="button"
                            :class="[
                                'min-w-8 rounded px-3 py-1',
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700',
                            ]"
                            @click="router.visit(link.url)"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="min-w-8 rounded border border-gray-200 px-3 py-1 text-gray-400 dark:border-gray-700"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
