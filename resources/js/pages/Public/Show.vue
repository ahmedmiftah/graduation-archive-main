<script setup lang="ts">
import Logo from '@/components/Brand/Logo.vue';
import FeedbackModal from '@/components/Feedback/FeedbackModal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Department {
    id: number;
    name: string;
}
interface Specialization {
    id: number;
    name: string;
}
interface Student {
    id: number;
    full_name: string;
    registration_number: string;
}
interface Document {
    id: number;
    document_type: string;
    file_path: string;
    is_final: boolean;
}
interface Examiner {
    id: number;
    full_name: string;
    title: string | null;
}
interface Evaluation {
    id: number;
    examiner_id: number;
    notes: string | null;
}
interface RelatedProject {
    id: number;
    project_title: string;
    academic_year: string;
    department: Department | null;
    specialization: Specialization | null;
    supervisor: { name: string } | null;
    students: Student[];
}

interface Project {
    id: number;
    project_title: string;
    description: string | null;
    academic_year: string;
    draft_file_path: string | null;
    final_score: string | null;
    visit_count: number;
    department: Department | null;
    specialization: Specialization | null;
    supervisor: { id: number; name: string } | null;
    students: Student[];
    documents: Document[];
    examiners: Examiner[];
    evaluations: Evaluation[];
}

defineProps<{
    project: Project;
    related: RelatedProject[];
}>();

const showFeedbackModal = ref(false);

function openFeedbackModal() {
    showFeedbackModal.value = true;
}

function closeFeedbackModal() {
    showFeedbackModal.value = false;
}
</script>

<template>
    <Head :title="project.project_title + ' — كلية التقنية الإلكترونية'" />

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
            <!-- BACK LINK -->
            <Link
                :href="route('public.browse')"
                class="mb-6 inline-flex items-center gap-1 text-sm text-text-muted transition-colors hover:text-primary"
            >
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
                    class="shrink-0"
                >
                    <path d="m15 18-6-6 6-6" />
                </svg>
                العودة للمشاريع
            </Link>

            <!-- PROJECT HEADER -->
            <div class="mb-6 rounded-xl border border-border bg-surface p-6">
                <div class="mb-2 flex flex-wrap items-start justify-between gap-4">
                    <h1 class="flex-1 font-display text-2xl font-bold leading-snug text-text-dark">
                        {{ project.project_title }}
                    </h1>
                    <div class="flex shrink-0 items-center gap-3">
                        <span class="inline-block rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">منجز</span>
                        <span class="inline-flex items-center gap-1 text-sm text-text-muted">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            {{ project.visit_count }} مشاهدة
                        </span>
                    </div>
                </div>

                <!-- INFO GRID -->
                <div class="mt-4 grid grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                    <div class="flex gap-2">
                        <span class="w-32 shrink-0 text-text-muted">القسم:</span>
                        <span class="font-medium text-text-dark">{{ project.department?.name ?? '—' }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="w-32 shrink-0 text-text-muted">التخصص:</span>
                        <span class="font-medium text-text-dark">{{ project.specialization?.name ?? '—' }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="w-32 shrink-0 text-text-muted">الفصل الدراسي:</span>
                        <span class="font-medium text-text-dark">{{ project.academic_year }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="w-32 shrink-0 text-text-muted">المشرف:</span>
                        <span class="font-medium text-text-dark">{{ project.supervisor?.name ?? '—' }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="w-32 shrink-0 text-text-muted">الدرجة النهائية:</span>
                        <span class="font-medium text-text-dark">
                            {{ project.final_score !== null ? project.final_score : 'غير محدد' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- STUDENTS SECTION -->
            <div v-if="project.students.length > 0" class="mb-6 rounded-xl border border-border bg-surface p-6">
                <h2 class="mb-4 font-display text-lg font-bold text-text-dark">طلبة المشروع</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="pb-2 text-right font-medium text-text-muted">الاسم</th>
                            <th class="pb-2 text-right font-medium text-text-muted">رقم القيد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="student in project.students" :key="student.id" class="border-b border-border/50 last:border-0">
                            <td class="py-2 text-text-dark">{{ student.full_name }}</td>
                            <td class="py-2 text-text-muted">{{ student.registration_number }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PROJECT SUMMARY / ABSTRACT SECTION -->
            <div v-if="project.description" class="mb-6 rounded-xl border border-border bg-surface p-6">
                <div class="mb-3 flex items-center gap-2">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="text-primary"
                    >
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" x2="8" y1="13" y2="13" />
                        <line x1="16" x2="8" y1="17" y2="17" />
                        <polyline points="10 9 9 9 8 9" />
                    </svg>
                    <h2 class="font-display text-lg font-bold text-text-dark">ملخص المشروع</h2>
                </div>
                <p class="whitespace-pre-line text-sm leading-relaxed text-text-dark">{{ project.description }}</p>
            </div>

            <!-- FULL FILE LOCKED NOTICE -->
            <div v-if="project.draft_file_path" class="mb-6 rounded-xl border border-border bg-surface p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gray-100">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="text-gray-400"
                        >
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-text-dark">الملف الكامل للمشروع</p>
                        <p class="mt-0.5 text-xs text-text-muted">
                            متاح فقط للمستخدمين المسجّلين — يرجى
                            <Link :href="route('login')" class="font-medium text-primary hover:underline">تسجيل الدخول</Link>
                            للوصول إليه.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-6 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    @click="openFeedbackModal"
                    class="rounded-lg border border-primary/20 bg-primary-light/10 px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary-light"
                >
                    إرسال ملاحظة عن المشروع
                </button>
            </div>

            <!-- RELATED PROJECTS -->
            <div v-if="related.length > 0" class="mt-8">
                <h2 class="mb-4 font-display text-xl font-bold text-text-dark">مشاريع مشابهة</h2>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="rel in related"
                        :key="rel.id"
                        class="flex flex-col rounded-xl border border-border bg-surface p-5 transition-all duration-200 hover:border-primary/40 hover:shadow-md"
                    >
                        <h3 class="mb-3 line-clamp-2 font-display text-base font-bold leading-snug text-text-dark">
                            {{ rel.project_title }}
                        </h3>
                        <div class="mb-3 flex flex-wrap gap-2">
                            <span class="inline-block rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                                {{ rel.department?.name ?? '—' }}
                            </span>
                            <span class="inline-block rounded-full bg-primary-light/15 px-2 py-0.5 text-xs font-medium text-primary-dark">
                                {{ rel.specialization?.name ?? '—' }}</span
                            >
                        </div>
                        <div class="mb-4 flex-1 space-y-1 text-xs text-text-muted">
                            <p>
                                الفصل الدراسي: <span class="font-medium text-text-dark">{{ rel.academic_year }}</span>
                            </p>
                            <p>
                                المشرف: <span class="font-medium text-text-dark">{{ rel.supervisor?.name ?? '—' }}</span>
                            </p>
                            <p>
                                عدد الطلبة: <span class="font-medium text-text-dark">{{ rel.students.length }}</span>
                            </p>
                        </div>
                        <Link
                            :href="route('public.show', { id: rel.id })"
                            class="mt-auto inline-flex items-center gap-1 text-sm font-medium text-primary transition-colors hover:text-primary-dark"
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
        </main>

        <FeedbackModal :show="showFeedbackModal" :projectId="project.id" :onClose="closeFeedbackModal" @submitted="closeFeedbackModal" />

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
