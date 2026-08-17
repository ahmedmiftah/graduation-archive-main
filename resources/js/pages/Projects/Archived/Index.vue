<script setup lang="ts">
import ExportButtons from '@/components/ExportButtons.vue';
import FilterPanel, { type FilterValues } from '@/components/FilterPanel.vue';
import SearchBar from '@/components/SearchBar.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { specializationBadgeColor } from '@/lib/specializationBadge';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

// ── Types ────────────────────────────────────────────────────────────

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

interface Project {
    id: number;
    project_title: string;
    academic_year: string;
    semester: string | null;
    degree_level: string;
    final_score: string | null;
    draft_file_path: string | null;
    specialization: Specialization | null;
    supervisor: Supervisor | null;
    students_count: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedProjects {
    data: Project[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

interface FilterOptions {
    departments: (Department & { specializations: Specialization[] })[];
    academic_years: string[];
    supervisors: Supervisor[];
}

// ── Props ────────────────────────────────────────────────────────────

const props = defineProps<{
    projects: PaginatedProjects;
    filterOptions: FilterOptions;
    filters: {
        search?: string;
        department_id?: string | number;
        specialization_id?: string | number;
        academic_year?: string;
        semester?: string;
        supervisor_id?: string | number;
        degree_level?: string;
        sort?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'المشاريع', href: '/projects' },
    { title: 'الأرشيف', href: '/projects/archived' },
];

// ── Filter state ─────────────────────────────────────────────────────

const searchQuery = ref(props.filters.search ?? '');
const suggestions = ref<string[]>([]);

const filters = reactive<FilterValues>({
    department_id: props.filters.department_id ?? '',
    specialization_id: props.filters.specialization_id ?? '',
    academic_year: props.filters.academic_year ?? '',
    semester: props.filters.semester ?? '',
    supervisor_id: props.filters.supervisor_id ?? '',
    degree_level: props.filters.degree_level ?? '',
    sort: props.filters.sort ?? 'created_at',
    status: '',
});

const allSpecs = computed(() => props.filterOptions.departments.flatMap((d) => d.specializations ?? []));

function gradeLabel(score: string | null): string {
    if (score === null) return '—';
    const value = Number(score);
    if (Number.isNaN(value)) return '—';
    if (value >= 90) return 'ممتاز';
    if (value >= 80) return 'جيد جداً';
    if (value >= 70) return 'جيد';
    if (value >= 60) return 'مقبول';
    return 'ضعيف';
}

function gradeBadgeColor(score: string | null): string {
    if (score === null) return 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
    const value = Number(score);
    if (Number.isNaN(value)) return 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
    if (value >= 80) return 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400';
    if (value >= 60) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400';
    return 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400';
}

function buildParams(): Record<string, string> {
    const p: Record<string, string> = {};
    if (searchQuery.value) p.search = searchQuery.value;
    if (filters.department_id) p.department_id = String(filters.department_id);
    if (filters.specialization_id) p.specialization_id = String(filters.specialization_id);
    if (filters.academic_year) p.academic_year = filters.academic_year;
    if (filters.semester) p.semester = filters.semester;
    if (filters.supervisor_id) p.supervisor_id = String(filters.supervisor_id);
    if (filters.degree_level) p.degree_level = filters.degree_level;
    if (filters.sort && filters.sort !== 'created_at') p.sort = filters.sort;
    return p;
}

function applyFilters() {
    router.get(route('projects.archived'), buildParams(), { preserveState: true, replace: true });
}

function onSearch(q: string) {
    searchQuery.value = q;
    applyFilters();
}

function onSelect(s: string) {
    searchQuery.value = s;
    applyFilters();
}

function onFilterChanged(val: FilterValues) {
    Object.assign(filters, val);
    applyFilters();
}

// ── Export links (carry the currently active filters) ────────────────

const exportPdfUrl = computed(() => route('projects.archived.export.pdf', buildParams()));
const exportExcelUrl = computed(() => route('projects.archived.export.excel', buildParams()));
</script>

<template>
    <Head title="أرشيف المشاريع" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-w-0 flex-1 flex-col gap-4 p-4" dir="rtl">
            <!-- ── Header ─────────────────────────────────────────── -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">أرشيف المشاريع</h1>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-sm font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        {{ projects.total }}
                    </span>
                </div>
            </div>

            <!-- ── Search bar + export ─────────────────────────────── -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <SearchBar
                        v-model="searchQuery"
                        placeholder="بحث في العنوان أو الوصف..."
                        :suggestions="suggestions"
                        @search="onSearch"
                        @select="onSelect"
                    />
                </div>
                <ExportButtons :pdf-url="exportPdfUrl" :excel-url="exportExcelUrl" />
            </div>

            <!-- ── Advanced filter panel ──────────────────────────── -->
            <FilterPanel
                :departments="filterOptions.departments"
                :specializations="allSpecs"
                :years="filterOptions.academic_years"
                :supervisors="filterOptions.supervisors"
                :model-value="filters"
                @filter-changed="onFilterChanged"
            />

            <!-- ── Results count ──────────────────────────────────── -->
            <p v-if="projects.from" class="text-xs text-gray-500 dark:text-gray-400">
                عرض {{ projects.from }}–{{ projects.to }} من {{ projects.total }} مشروع
            </p>

            <!-- ── Table ──────────────────────────────────────────── -->
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">العنوان</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">التخصص</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">المشرف</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الفصل الدراسي</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الدرجة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">التقدير</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الملف</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr v-for="project in projects.data" :key="project.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="max-w-xs px-4 py-3">
                                <a
                                    :href="route('projects.show', { project: project.id, from: 'archived' })"
                                    class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                                >
                                    {{ project.project_title }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span
                                    v-if="project.specialization"
                                    :title="project.specialization.name"
                                    :class="['rounded-full px-2 py-0.5 text-xs font-medium', specializationBadgeColor(project.specialization.name)]"
                                >
                                    {{ project.specialization.name }}
                                </span>
                                <span v-else class="text-sm text-gray-400">—</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ project.supervisor?.full_name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ project.semester ? `${project.semester} ${project.academic_year}` : project.academic_year }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ project.final_score ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', gradeBadgeColor(project.final_score)]">
                                    {{ gradeLabel(project.final_score) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <a
                                    v-if="project.draft_file_path"
                                    :href="`/storage/${project.draft_file_path}`"
                                    target="_blank"
                                    download
                                    title="تنزيل ملف المشروع"
                                    class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40"
                                >
                                    ↓ PDF
                                </a>
                                <span v-else class="text-sm text-gray-400">—</span>
                            </td>
                        </tr>
                        <tr v-if="projects.data.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">لا توجد مشاريع مؤرشفة مطابقة</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── Pagination ─────────────────────────────────────── -->
            <div v-if="projects.last_page > 1" class="flex items-center justify-between text-sm">
                <p class="text-gray-600 dark:text-gray-400">صفحة {{ projects.current_page }} من {{ projects.last_page }}</p>
                <div class="flex gap-1">
                    <template v-for="link in projects.links" :key="link.label">
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
                        <span v-else class="min-w-8 rounded border border-gray-200 px-3 py-1 text-gray-400 dark:border-gray-700" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
