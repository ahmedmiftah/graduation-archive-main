<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Logo from '@/components/Brand/Logo.vue';

interface Department { id: number; name: string; }
interface Specialization { id: number; name: string; }
interface Student { id: number; full_name: string; registration_number: string; }
interface Document { id: number; document_type: string; file_path: string; is_final: boolean; }
interface Examiner { id: number; full_name: string; title: string | null; }
interface Evaluation { id: number; examiner_id: number; notes: string | null; }
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
</script>

<template>
    <Head :title="project.project_title + ' — كلية التقنية الإلكترونية'" />

    <div class="min-h-screen bg-background font-body text-text-dark" dir="rtl">

        <!-- HEADER -->
        <header class="sticky top-0 z-50 bg-surface border-b border-border shadow-sm">
            <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
                <Link :href="route('home')" class="flex items-center gap-3">
                    <Logo size="md" />
                </Link>
                <span class="font-display font-bold text-primary hidden md:block">نظام أرشفة مشاريع التخرج</span>
                <Link
                    :href="route('login')"
                    class="inline-flex items-center px-5 py-2 rounded-lg border-2 border-primary text-primary font-body text-sm font-medium hover:bg-primary hover:text-white transition-colors"
                >
                    تسجيل الدخول
                </Link>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-6 py-8">

            <!-- BACK LINK -->
            <Link
                :href="route('public.browse')"
                class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-primary mb-6 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="m15 18-6-6 6-6"/></svg>
                العودة للمشاريع
            </Link>

            <!-- PROJECT HEADER -->
            <div class="bg-surface border border-border rounded-xl p-6 mb-6">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-2">
                    <h1 class="font-display font-bold text-2xl text-text-dark leading-snug flex-1">
                        {{ project.project_title }}
                    </h1>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full font-medium">مؤرشف</span>
                        <span class="inline-flex items-center gap-1 text-sm text-text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            {{ project.visit_count }} مشاهدة
                        </span>
                    </div>
                </div>

                <!-- INFO GRID -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 mt-4 text-sm">
                    <div class="flex gap-2">
                        <span class="text-text-muted w-32 shrink-0">القسم:</span>
                        <span class="text-text-dark font-medium">{{ project.department?.name ?? '—' }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-text-muted w-32 shrink-0">التخصص:</span>
                        <span class="text-text-dark font-medium">{{ project.specialization?.name ?? '—' }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-text-muted w-32 shrink-0">السنة الدراسية:</span>
                        <span class="text-text-dark font-medium">{{ project.academic_year }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-text-muted w-32 shrink-0">المشرف:</span>
                        <span class="text-text-dark font-medium">{{ project.supervisor?.name ?? '—' }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-text-muted w-32 shrink-0">الدرجة النهائية:</span>
                        <span class="text-text-dark font-medium">
                            {{ project.final_score !== null ? project.final_score : 'غير محدد' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- STUDENTS SECTION -->
            <div v-if="project.students.length > 0" class="bg-surface border border-border rounded-xl p-6 mb-6">
                <h2 class="font-display font-bold text-lg text-text-dark mb-4">طلبة المشروع</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="text-right pb-2 font-medium text-text-muted">الاسم</th>
                            <th class="text-right pb-2 font-medium text-text-muted">رقم القيد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="student in project.students"
                            :key="student.id"
                            class="border-b border-border/50 last:border-0"
                        >
                            <td class="py-2 text-text-dark">{{ student.full_name }}</td>
                            <td class="py-2 text-text-muted">{{ student.registration_number }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PROJECT SUMMARY / ABSTRACT SECTION -->
            <div v-if="project.description" class="bg-surface border border-border rounded-xl p-6 mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <h2 class="font-display font-bold text-lg text-text-dark">ملخص المشروع</h2>
                </div>
                <p class="text-sm text-text-dark leading-relaxed whitespace-pre-line">{{ project.description }}</p>
            </div>

            <!-- FULL FILE LOCKED NOTICE -->
            <div v-if="project.draft_file_path" class="bg-surface border border-border rounded-xl p-6 mb-6">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <div>
                        <p class="font-medium text-sm text-text-dark">الملف الكامل للمشروع</p>
                        <p class="text-xs text-text-muted mt-0.5">متاح فقط للمستخدمين المسجّلين — يرجى
                            <Link :href="route('login')" class="text-primary hover:underline font-medium">تسجيل الدخول</Link>
                            للوصول إليه.
                        </p>
                    </div>
                </div>
            </div>


            <!-- RELATED PROJECTS -->
            <div v-if="related.length > 0" class="mt-8">
                <h2 class="font-display font-bold text-xl text-text-dark mb-4">مشاريع مشابهة</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="rel in related"
                        :key="rel.id"
                        class="bg-surface border border-border rounded-xl p-5 hover:border-primary/40 hover:shadow-md transition-all duration-200 flex flex-col"
                    >
                        <h3 class="font-display font-bold text-base text-text-dark mb-3 line-clamp-2 leading-snug">
                            {{ rel.project_title }}
                        </h3>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span class="inline-block bg-primary/10 text-primary text-xs px-2 py-0.5 rounded-full font-medium">
                                {{ rel.department?.name ?? '—' }}
                            </span>
                            <span class="inline-block bg-primary-light/15 text-primary-dark text-xs px-2 py-0.5 rounded-full font-medium">
                                {{ rel.specialization?.name ?? '—' }}
                            </span>
                        </div>
                        <div class="text-xs text-text-muted space-y-1 mb-4 flex-1">
                            <p>السنة الدراسية: <span class="text-text-dark font-medium">{{ rel.academic_year }}</span></p>
                            <p>المشرف: <span class="text-text-dark font-medium">{{ rel.supervisor?.name ?? '—' }}</span></p>
                            <p>عدد الطلبة: <span class="text-text-dark font-medium">{{ rel.students.length }}</span></p>
                        </div>
                        <Link
                            :href="route('public.show', { id: rel.id })"
                            class="mt-auto inline-flex items-center gap-1 text-sm text-primary font-medium hover:text-primary-dark transition-colors"
                        >
                            عرض التفاصيل
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="rotate-180 shrink-0"><path d="m9 18 6-6-6-6"/></svg>
                        </Link>
                    </div>
                </div>
            </div>

        </main>

        <!-- FOOTER -->
        <footer class="bg-primary-dark py-6 border-t border-primary/20 mt-16">
            <div class="max-w-6xl mx-auto px-6 text-center">
                <p class="font-body text-sm text-primary-light/50">
                    جميع الحقوق محفوظة &copy; {{ new Date().getFullYear() }} — كلية التقنية الإلكترونية
                </p>
            </div>
        </footer>

    </div>
</template>
