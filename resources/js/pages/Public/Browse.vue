<script setup lang="ts">
import Logo from '@/components/Brand/Logo.vue';
import FeedbackModal from '@/components/Feedback/FeedbackModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Department {
    id: number;
    name: string;
}
interface Specialization {
    id: number;
    name: string;
    department_id: number;
}
interface Student {
    id: number;
    full_name: string;
    registration_number: string;
}
interface Project {
    id: number;
    project_title: string;
    academic_year: string;
    department: Department | null;
    specialization: Specialization | null;
    supervisor: { id: number; name: string } | null;
    degree_level: string;
    students: Student[];
}
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
interface Paginated {
    data: Project[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

const props = defineProps<{
    projects: Paginated;
    departments: Department[];
    specializations: Specialization[];
    years: string[];
    filters: { search?: string; department_id?: string; specialization_id?: string; academic_year?: string; degree_level?: string };
}>();

const search = ref(props.filters.search ?? '');
const departmentId = ref(props.filters.department_id ?? '');
const specId = ref(props.filters.specialization_id ?? '');
const year = ref(props.filters.academic_year ?? '');
const degreeLevel = ref(props.filters.degree_level ?? '');
const showFeedbackModal = ref(false);
const selectedProjectId = ref<number | null>(null);

const suggestions = ref<string[]>([]);
const showDropdown = ref(false);
let timer: ReturnType<typeof setTimeout> | null = null;

async function loadSuggestions(q: string) {
    if (q.length < 2) { suggestions.value = []; return; }
    try {
        const res = await fetch(route('search.suggestions') + '?q=' + encodeURIComponent(q));
        suggestions.value = await res.json() as string[];
    } catch {
        suggestions.value = [];
    }
}

function onInput(e: Event) {
    const val = (e.target as HTMLInputElement).value;
    search.value = val;
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => loadSuggestions(val), 300);
    showDropdown.value = true;
}

function selectSuggestion(s: string) {
    search.value = s;
    showDropdown.value = false;
    applyFilters();
}

function onBlur() {
    setTimeout(() => { showDropdown.value = false; }, 200);
}

const filteredSpecs = computed(() =>
    departmentId.value ? props.specializations.filter((s) => s.department_id === Number(departmentId.value)) : props.specializations,
);

watch(departmentId, () => {
    specId.value = '';
});

function openFeedbackModal(projectId: number) {
    selectedProjectId.value = projectId;
    showFeedbackModal.value = true;
}

function closeFeedbackModal() {
    showFeedbackModal.value = false;
    selectedProjectId.value = null;
}

function applyFilters() {
    router.get(
        route('public.browse'),
        {
            search: search.value || undefined,
            department_id: departmentId.value || undefined,
            specialization_id: specId.value || undefined,
            academic_year: year.value || undefined,
            degree_level: degreeLevel.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
}

function resetFilters() {
    router.get(route('public.browse'), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="تصفح المشاريع المؤرشفة — كلية التقنية الإلكترونية" />

    <div class="min-h-screen bg-background font-body text-text-dark" dir="rtl">
        <!-- HEADER -->
        <header class="sticky top-0 z-50 border-b border-border bg-surface shadow-sm">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                <Link :href="route('home')" class="flex items-center gap-3">
                    <Logo size="md" />
                </Link>
                <span class="hidden font-display font-bold text-primary md:block">نظام أرشفة مشاريع التخرج</span>
                <Link
                    :href="route('login')"
                    class="inline-flex items-center rounded-lg border-2 border-primary px-5 py-2 font-body text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white"
                >
                    تسجيل الدخول
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-8">
            <!-- PAGE TITLE -->
            <h1 class="mb-6 font-display text-2xl font-bold text-text-dark">المشاريع المؤرشفة</h1>

            <!-- SEARCH & FILTER BAR -->
            <div class="mb-6 rounded-xl border border-border bg-surface p-4">
                <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="relative col-span-1 sm:col-span-2 lg:col-span-1">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="ابحث عن مشروع..."
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-right text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                            @input="onInput"
                            @focus="showDropdown = suggestions.length > 0"
                            @blur="onBlur"
                            @keydown.enter.prevent="applyFilters"
                        />
                        <ul
                            v-if="showDropdown && suggestions.length > 0"
                            class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-border bg-surface py-1 shadow-lg"
                        >
                            <li
                                v-for="s in suggestions"
                                :key="s"
                                class="cursor-pointer px-4 py-2 text-sm text-text-dark hover:bg-primary/5 hover:text-primary"
                                @mousedown.prevent="selectSuggestion(s)"
                            >
                                {{ s }}
                            </li>
                        </ul>
                    </div>
                    <select
                        v-model="departmentId"
                        class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                    >
                        <option value="">كل الأقسام</option>
                        <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                    <select
                        v-model="specId"
                        class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                        :disabled="!departmentId && filteredSpecs.length === specializations.length"
                    >
                        <option value="">كل التخصصات</option>
                        <option v-for="s in filteredSpecs" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <select
                        v-model="year"
                        class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                    >
                        <option value="">كل السنوات</option>
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <select
                        v-model="degreeLevel"
                        class="col-span-1 rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 sm:col-span-2 lg:col-span-1"
                    >
                        <option value="">كل الفئات</option>
                        <option value="diploma">دبلوم</option>
                        <option value="bachelor">بكالوريوس</option>
                        <option value="master">ماجستير</option>
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="applyFilters"
                        class="rounded-lg bg-primary px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
                    >
                        بحث
                    </button>
                    <button @click="resetFilters" class="rounded-lg px-4 py-2 text-sm text-text-muted transition-colors hover:text-text-dark">
                        إعادة تعيين
                    </button>
                </div>
            </div>

            <!-- RESULTS INFO -->
            <p class="mb-4 text-sm text-text-muted">عرض {{ projects.data.length }} من أصل {{ projects.total }} مشروع مؤرشف</p>

            <!-- PROJECTS GRID -->
            <div v-if="projects.data.length > 0" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="project in projects.data"
                    :key="project.id"
                    class="flex flex-col rounded-xl border border-border bg-surface p-5 transition-all duration-200 hover:border-primary/40 hover:shadow-md"
                >
                    <h2 class="mb-3 line-clamp-2 font-display text-base font-bold leading-snug text-text-dark">
                        {{ project.project_title }}
                    </h2>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <span class="inline-block rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                            {{ project.department?.name ?? '—' }}
                        </span>
                        <span class="inline-block rounded-full bg-primary-light/15 px-2 py-0.5 text-xs font-medium text-primary-dark">
                            {{ project.specialization?.name ?? '—' }}
                        </span>
                    </div>
                    <div class="mb-4 flex-1 space-y-1 text-xs text-text-muted">
                        <p>
                            السنة الدراسية: <span class="font-medium text-text-dark">{{ project.academic_year }}</span>
                        </p>
                        <p>
                            الفئة:
                            <span class="font-medium text-text-dark">{{
                                project.degree_level === 'diploma' ? 'دبلوم' : project.degree_level === 'master' ? 'ماجستير' : 'بكالوريوس'
                            }}</span>
                        </p>
                        <p>
                            المشرف: <span class="font-medium text-text-dark">{{ project.supervisor?.name ?? '—' }}</span>
                        </p>
                        <p>
                            عدد الطلبة: <span class="font-medium text-text-dark">{{ project.students.length }}</span>
                        </p>
                    </div>
                    <div class="mt-auto flex flex-col gap-3">
                        <button
                            type="button"
                            @click="openFeedbackModal(project.id)"
                            class="w-full rounded-lg border border-primary/20 bg-primary-light/10 px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary-light"
                        >
                            إرسال ملاحظة عن المشروع
                        </button>
                        <Link
                            :href="route('public.show', { id: project.id })"
                            class="inline-flex items-center justify-center gap-1 text-sm font-medium text-primary transition-colors hover:text-primary-dark"
                        >
                            عرض التفاصيل
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="shrink-0 rotate-180"
                            >
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- EMPTY STATE -->
            <div v-else class="py-20 text-center">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="mx-auto mb-4 h-16 w-16 text-border"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <p class="mb-4 text-lg font-medium text-text-muted">لا توجد مشاريع تطابق البحث</p>
                <button
                    @click="resetFilters"
                    class="rounded-lg bg-primary px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
                >
                    إعادة تعيين الفلاتر
                </button>
            </div>

            <!-- PAGINATION -->
            <div v-if="projects.last_page > 1" class="mt-8 flex flex-wrap justify-center gap-1">
                <template v-for="link in projects.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg border px-3 py-1.5 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'border-primary bg-primary text-white'
                                : 'border-border text-text-muted hover:border-primary hover:text-primary'
                        "
                        v-html="link.label"
                    />
                    <span v-else class="rounded-lg border border-border px-3 py-1.5 text-sm text-text-muted opacity-50" v-html="link.label" />
                </template>
            </div>
        </main>

        <FeedbackModal :show="showFeedbackModal" :projectId="selectedProjectId" :onClose="closeFeedbackModal" @submitted="closeFeedbackModal" />

        <!-- FOOTER -->
        <footer class="mt-16 border-t border-primary/20 bg-primary-dark py-6">
            <div class="mx-auto max-w-6xl px-6 text-center">
                <p class="font-body text-sm text-primary-light/50">
                    جميع الحقوق محفوظة &copy; {{ new Date().getFullYear() }} — كلية التقنية الإلكترونية
                </p>
            </div>
        </footer>
    </div>
</template>
