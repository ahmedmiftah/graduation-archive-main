<script setup lang="ts">
import AssignFacultyMemberModal from '@/components/AssignFacultyMemberModal.vue';
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import ScoreInput from '@/components/ScoreInput.vue';
import SimilarityWarning from '@/components/SimilarityWarning.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { statusColor, statusLabel } from '@/lib/statusBadge';
import { type BreadcrumbItem, type Department, type SharedData, type SimilarProject } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Specialization {
    id: number;
    name: string;
}
interface Supervisor {
    id: number;
    full_name: string;
}
interface ProjectStatus {
    id: number;
    status_name: string;
}
interface ProjectStudent {
    id: number;
    full_name: string;
    registration_number: string;
    status: string;
}
interface ProjectDocument {
    id: number;
    file_path: string;
    document_type: string;
    is_final: boolean;
}
interface FacultyMember {
    id: number;
    full_name: string;
    degree?: { degree_code: string } | null;
    departments?: Department[];
}
interface Evaluation {
    id: number;
    faculty_member_id: number;
    notes: string;
    created_at: string;
}

interface Project {
    id: number;
    project_title: string;
    description: string;
    academic_year: string;
    semester: string | null;
    draft_file_path: string | null;
    final_score: string | null;
    visit_count: number;
    department: Department | null;
    specialization: Specialization | null;
    supervisor: Supervisor | null;
    current_status: ProjectStatus | null;
    students: ProjectStudent[];
    documents: ProjectDocument[];
    faculty_members: FacultyMember[];
    evaluations: Evaluation[];
}

const props = defineProps<{
    project: Project;
    availableExaminers: FacultyMember[];
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});
const dismissedWarning = ref(false);
const similarProjects = computed<SimilarProject[]>(() => (dismissedWarning.value ? [] : (flash.value.similarity_warning ?? [])));
const userRole = computed(() => (page.props.auth.user as { role?: string }).role ?? '');
const maxExaminers = computed(() => page.props.systemSettings?.examiners_per_project ?? 2);

// Tracks whether this page was opened from the archived-projects list so the
// breadcrumb, sidebar highlight, and score editability all match that context.
const cameFromArchive = computed(() => new URLSearchParams(window.location.search).get('from') === 'archived');

const breadcrumbs = computed<BreadcrumbItem[]>(() =>
    cameFromArchive.value
        ? [
              { title: 'لوحة تحكم أرشفة المشاريع', href: '/projects/archived' },
              { title: props.project.project_title, href: '#' },
          ]
        : [
              { title: 'لوحة التحكم', href: '/dashboard' },
              { title: 'المشاريع', href: '/projects' },
              { title: props.project.project_title, href: '#' },
          ],
);

// ── Permissions ───────────────────────────────────────────────────
const canEdit = computed(() => ['dept_staff', 'dept_manager', 'super_admin'].includes(userRole.value));
const canDelete = computed(() => ['dept_manager', 'super_admin'].includes(userRole.value));
const canManage = computed(() => ['dept_manager', 'super_admin'].includes(userRole.value));

// Score entry is only allowed when reached from the archived-projects list —
// the regular project details view is read-only for the score.
const canEditScore = computed(() => canManage.value && cameFromArchive.value);

// ── Project actions ────────────────────────────────────────────────
const showConfirmDelete = ref(false);

function deleteProject() {
    router.delete(route('projects.destroy', [props.project.id]), {
        onFinish: () => (showConfirmDelete.value = false),
    });
}

// ── Faculty member assign / remove ─────────────────────────────────
const showAssignModal = ref(false);
const confirmRemoveExaminer = ref<FacultyMember | null>(null);

function removeExaminer() {
    if (!confirmRemoveExaminer.value) return;
    router.delete(route('projects.remove-faculty-member', [props.project.id, confirmRemoveExaminer.value.id]), {
        onFinish: () => (confirmRemoveExaminer.value = null),
    });
}

// ── Evaluation helpers ─────────────────────────────────────────────
function evaluationFor(facultyMemberId: number): Evaluation | null {
    return props.project.evaluations.find((e) => e.faculty_member_id === facultyMemberId) ?? null;
}

// ── Score helpers ──────────────────────────────────────────────────
const PASS_THRESHOLD = 50;

const finalScore = computed(() => {
    if (props.project.final_score === null || props.project.final_score === '') return null;
    const n = Number(props.project.final_score);
    return isNaN(n) ? null : n;
});

const scoreIsPass = computed(() => finalScore.value !== null && finalScore.value >= PASS_THRESHOLD);


const pdfUrl = computed(() => (props.project.draft_file_path ? `/storage/${props.project.draft_file_path}` : null));

const pdfFileName = computed(() => props.project.draft_file_path?.split('/').pop() ?? null);

const studentStatusLabel: Record<string, string> = {
    active: 'نشط',
    withdrawn: 'منسحب',
    completed: 'مكتمل',
};
</script>

<template>
    <Head :title="project.project_title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <!-- Flash messages -->
            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                {{ flash.error }}
            </div>

            <SimilarityWarning
                :show="similarProjects.length > 0"
                :similar-projects="similarProjects"
                @continue="dismissedWarning = true"
                @change-title="dismissedWarning = true"
            />

            <!-- Header row -->
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ project.project_title }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ project.semester ? `${project.semester} ${project.academic_year}` : project.academic_year }}</span>
                        <span>•</span>
                        <span>{{ project.visit_count }} مشاهدة</span>
                        <span
                            v-if="project.current_status"
                            :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', statusColor(project.current_status.status_name)]"
                        >
                            {{ statusLabel(project.current_status.status_name) }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="canEdit"
                        :href="route('projects.edit', [project.id])"
                        class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-600"
                    >
                        تعديل
                    </a>
                    <button
                        v-if="canDelete"
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                        @click="showConfirmDelete = true"
                    >
                        حذف
                    </button>
                    <a
                        :href="route('projects.index')"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        العودة للقائمة
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main column -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Description -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-gray-200">الوصف</h2>
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            {{ project.description }}
                        </p>
                    </div>

                    <!-- Students -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-gray-200">
                            الطلاب
                            <span class="text-sm font-normal text-gray-400">({{ project.students.length }})</span>
                        </h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">#</th>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">الاسم الكامل</th>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">رقم القيد</th>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-transparent">
                                    <tr v-for="(student, idx) in project.students" :key="student.id">
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ idx + 1 }}</td>
                                        <td class="px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ student.full_name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ student.registration_number }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                            {{ studentStatusLabel[student.status] ?? student.status }}
                                        </td>
                                    </tr>
                                    <tr v-if="project.students.length === 0">
                                        <td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">لا يوجد طلاب مسجلون</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Faculty members (examiners) + Evaluations -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200">
                                المناقشون
                                <span class="text-sm font-normal text-gray-400">({{ project.faculty_members.length }}/{{ maxExaminers }})</span>
                            </h2>
                            <button
                                v-if="canManage && project.faculty_members.length < maxExaminers"
                                type="button"
                                class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
                                @click="showAssignModal = true"
                            >
                                + تعيين عضو هيئة تدريس
                            </button>
                        </div>

                        <div v-if="project.faculty_members.length > 0" class="space-y-4">
                            <div
                                v-for="member in project.faculty_members"
                                :key="member.id"
                                class="rounded-lg border border-gray-100 p-4 dark:border-gray-700"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400"
                                        >
                                            <span class="text-sm font-bold">{{ member.full_name.charAt(0) }}</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ member.full_name }}</p>
                                            <p v-if="member.degree" class="text-xs text-gray-500">{{ member.degree.degree_code }}</p>
                                            <p v-if="member.departments?.length" class="text-xs text-gray-400">
                                                {{ member.departments.map((d) => d.name).join('، ') }}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        v-if="canManage"
                                        type="button"
                                        class="shrink-0 rounded px-2 py-1 text-xs text-red-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20"
                                        @click="confirmRemoveExaminer = member"
                                    >
                                        إزالة
                                    </button>
                                </div>

                                <!-- Evaluation notes for this faculty member -->
                                <div v-if="evaluationFor(member.id)" class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                                    <p class="mb-1 text-xs font-medium text-gray-400">ملاحظات التقييم</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ evaluationFor(member.id)!.notes }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500">لم يتم تعيين ممتحنين بعد</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-5">
                    <!-- Meta info -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">تفاصيل المشروع</h2>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">القسم</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ project.department?.name ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">التخصص</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ project.specialization?.name ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">المشرف</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ project.supervisor?.full_name ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">الفصل الدراسي</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ project.semester ? `${project.semester} ${project.academic_year}` : project.academic_year }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Score card -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">الدرجة النهائية</h2>

                        <!-- Manager view (only when opened from the archive): full ScoreInput (display + input form) -->
                        <ScoreInput v-if="canEditScore" :project-id="project.id" :current-score="project.final_score" />

                        <!-- Read-only display -->
                        <template v-else>
                            <div v-if="finalScore !== null" class="flex items-center gap-3">
                                <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ finalScore }}</span>
                                <span
                                    :class="[
                                        'rounded-full px-3 py-1 text-sm font-medium',
                                        scoreIsPass
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400',
                                    ]"
                                >
                                    {{ scoreIsPass ? 'ناجح' : 'راسب' }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-500">لم تُسجَّل درجة بعد</p>
                        </template>
                    </div>

                    <!-- PDF Document -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">ملف المشروع</h2>
                        <div v-if="pdfUrl" class="flex flex-col gap-3">
                            <div class="flex items-center gap-3 rounded-lg border border-gray-100 p-3 dark:border-gray-700">
                                <span class="text-2xl">📄</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-200">{{ pdfFileName }}</p>
                                    <p class="text-xs text-gray-500">PDF</p>
                                </div>
                            </div>
                            <a
                                :href="pdfUrl"
                                target="_blank"
                                download
                                class="flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                ↓ تنزيل الملف
                            </a>
                        </div>
                        <p v-else class="text-sm text-gray-500">لا يوجد ملف مرفق</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm delete project -->
        <ConfirmDelete
            :show="showConfirmDelete"
            :item-name="project.project_title"
            @confirmed="deleteProject"
            @cancelled="showConfirmDelete = false"
        />

        <!-- Confirm remove examiner -->
        <ConfirmDelete
            :show="!!confirmRemoveExaminer"
            :item-name="confirmRemoveExaminer?.full_name"
            @confirmed="removeExaminer"
            @cancelled="confirmRemoveExaminer = null"
        />

        <!-- Assign faculty member modal -->
        <AssignFacultyMemberModal
            :show="showAssignModal"
            :project-id="project.id"
            :available-examiners="availableExaminers"
            @assigned="showAssignModal = false"
            @cancelled="showAssignModal = false"
        />
    </AppLayout>
</template>
