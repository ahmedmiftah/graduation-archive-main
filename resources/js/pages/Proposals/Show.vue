<script setup lang="ts">
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import ProposalFormModal from '@/components/Proposals/ProposalFormModal.vue';
import SimilarityWarning from '@/components/SimilarityWarning.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { proposalStatusColor, proposalStatusLabel } from '@/lib/proposalStatusBadge';
import { type BreadcrumbItem, type Department, type SharedData, type SimilarProject } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Specialization {
    id: number;
    name: string;
    department_id: number;
}
interface Supervisor {
    id: number;
    full_name: string;
}
interface ProposalStudent {
    id: number;
    full_name: string;
    registration_number: string;
    phone_number: string | null;
}
interface LinkedProposal {
    id: number;
    title: string;
    status: string;
}
interface LinkedProject {
    id: number;
    project_title: string;
}

interface Proposal {
    id: number;
    title: string;
    description: string;
    status: string;
    academic_year: string;
    semester: string;
    submission_date: string | null;
    rejection_reason: string | null;
    supervisor_note: string | null;
    department_note: string | null;
    form_file_path: string | null;
    proposal_file_path: string | null;
    specialization_id: number;
    supervisor_id: number | null;
    department: Department | null;
    specialization: Specialization | null;
    supervisor: Supervisor | null;
    creator: { id: number; name: string } | null;
    students: ProposalStudent[];
    replaces: LinkedProposal | null;
    replaced_by: LinkedProposal | null;
    project: LinkedProject | null;
}

const props = defineProps<{
    proposal: Proposal;
    departments: Department[];
    specializations: Specialization[];
    supervisors: Supervisor[];
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});
const dismissedWarning = ref(false);
const similarProjects = computed<SimilarProject[]>(() => (dismissedWarning.value ? [] : (flash.value.similarity_warning ?? [])));
const userRole = computed(() => (page.props.auth.user as { role?: string }).role ?? '');
const userDeptId = computed(() => page.props.auth.user?.department_id ?? null);

const sameDept = computed(() => props.proposal.department?.id === userDeptId.value);
const canEdit = computed(
    () => userRole.value === 'super_admin' || (['dept_manager', 'dept_staff'].includes(userRole.value) && sameDept.value),
);
const canDelete = computed(() => userRole.value === 'super_admin' || (userRole.value === 'dept_manager' && sameDept.value));
const canChangeStatus = computed(() => userRole.value === 'super_admin' || (userRole.value === 'dept_manager' && sameDept.value));
const isActive = computed(() => ['pending', 'needs_revision'].includes(props.proposal.status));

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'المقترحات', href: '/proposals' },
    { title: props.proposal.title, href: '#' },
]);

// ── Edit / Replace modal ─────────────────────────────────────────────
const showEditModal = ref(false);
const showReplaceModal = ref(false);

// ── Delete ────────────────────────────────────────────────────────
const showConfirmDelete = ref(false);
function deleteProposal() {
    router.delete(route('proposals.destroy', [props.proposal.id]), {
        onFinish: () => (showConfirmDelete.value = false),
    });
}

// ── Change status ─────────────────────────────────────────────────
const showRejectForm = ref(false);
const rejectionReason = ref('');
const statusProcessing = ref(false);
const supervisorNoteInput = ref(props.proposal.supervisor_note ?? '');
const departmentNoteInput = ref(props.proposal.department_note ?? '');

function submitAction(action: 'reject' | 'request_revision' | 'approve') {
    if (action === 'reject' && !showRejectForm.value) {
        showRejectForm.value = true;
        return;
    }

    statusProcessing.value = true;
    router.post(
        route('proposals.change-status', [props.proposal.id]),
        {
            action,
            rejection_reason: action === 'reject' ? rejectionReason.value : null,
            supervisor_note: supervisorNoteInput.value || null,
            department_note: departmentNoteInput.value || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                statusProcessing.value = false;
                showRejectForm.value = false;
                rejectionReason.value = '';
            },
        },
    );
}

const studentStatusHeaders = ['#', 'الاسم', 'رقم القيد', 'رقم الهاتف'];
</script>

<template>
    <Head :title="proposal.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
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

            <!-- Header -->
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ proposal.title }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ proposal.semester }} {{ proposal.academic_year }}</span>
                        <span
                            :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', proposalStatusColor(proposal.status)]"
                        >
                            {{ proposalStatusLabel(proposal.status) }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="proposal.project"
                        :href="route('projects.show', [proposal.project.id])"
                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                    >
                        عرض المشروع
                    </a>
                    <button
                        v-if="canEdit"
                        type="button"
                        class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-600"
                        @click="showEditModal = true"
                    >
                        تعديل
                    </button>
                    <button
                        v-if="canEdit && proposal.status === 'needs_revision'"
                        type="button"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        @click="showReplaceModal = true"
                    >
                        إضافة مقترح بديل
                    </button>
                    <button
                        v-if="canDelete"
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                        @click="showConfirmDelete = true"
                    >
                        حذف
                    </button>
                    <a
                        :href="route('proposals.index')"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        العودة للقائمة
                    </a>
                </div>
            </div>

            <!-- Traceability links -->
            <div v-if="proposal.replaces || proposal.replaced_by" class="flex flex-wrap gap-2 text-sm">
                <a
                    v-if="proposal.replaces"
                    :href="route('proposals.show', [proposal.replaces.id])"
                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300"
                >
                    ← المقترح السابق: {{ proposal.replaces.title }}
                </a>
                <a
                    v-if="proposal.replaced_by"
                    :href="route('proposals.show', [proposal.replaced_by.id])"
                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300"
                >
                    المقترح البديل: {{ proposal.replaced_by.title }} →
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main column -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Description -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-gray-200">الوصف</h2>
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ proposal.description }}</p>
                    </div>

                    <!-- Students -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-gray-200">
                            الطلاب
                            <span class="text-sm font-normal text-gray-400">({{ proposal.students.length }})</span>
                        </h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th
                                            v-for="h in studentStatusHeaders"
                                            :key="h"
                                            class="px-4 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-400"
                                        >
                                            {{ h }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-transparent">
                                    <tr v-for="(student, idx) in proposal.students" :key="student.id">
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ idx + 1 }}</td>
                                        <td class="px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ student.full_name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ student.registration_number }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ student.phone_number ?? '—' }}</td>
                                    </tr>
                                    <tr v-if="proposal.students.length === 0">
                                        <td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">لا يوجد طلاب مسجلون</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Rejection reason -->
                    <div
                        v-if="proposal.status === 'rejected' && proposal.rejection_reason"
                        class="rounded-xl border border-red-200 bg-red-50 p-5 dark:border-red-900/40 dark:bg-red-900/10"
                    >
                        <h2 class="mb-2 text-base font-semibold text-red-800 dark:text-red-400">سبب الرفض</h2>
                        <p class="text-sm text-red-700 dark:text-red-400">{{ proposal.rejection_reason }}</p>
                    </div>

                    <!-- Supervisor / department notes -->
                    <div
                        v-if="proposal.supervisor_note || proposal.department_note"
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-gray-200">الملاحظات</h2>
                        <div v-if="proposal.supervisor_note" class="mb-3">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">ملاحظات المشرف</p>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ proposal.supervisor_note }}</p>
                        </div>
                        <div v-if="proposal.department_note">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">ملاحظات القسم</p>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ proposal.department_note }}</p>
                        </div>
                    </div>

                    <!-- Take action -->
                    <div
                        v-if="canChangeStatus && isActive"
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-gray-200">اتخاذ إجراء</h2>

                        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">ملاحظات المشرف (اختياري)</label>
                                <textarea
                                    v-model="supervisorNoteInput"
                                    rows="2"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">ملاحظات القسم (اختياري)</label>
                                <textarea
                                    v-model="departmentNoteInput"
                                    rows="2"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                :disabled="statusProcessing"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                @click="submitAction('reject')"
                            >
                                رفض
                            </button>
                            <button
                                type="button"
                                :disabled="statusProcessing"
                                class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600 disabled:opacity-50"
                                @click="submitAction('request_revision')"
                            >
                                طلب تعديل
                            </button>
                            <button
                                type="button"
                                :disabled="statusProcessing"
                                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                                @click="submitAction('approve')"
                            >
                                اعتماد
                            </button>
                        </div>
                        <div v-if="showRejectForm" class="mt-3 space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">سبب الرفض (إجباري)</label>
                            <textarea
                                v-model="rejectionReason"
                                rows="3"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            />
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    :disabled="statusProcessing || !rejectionReason.trim()"
                                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                    @click="submitAction('reject')"
                                >
                                    تأكيد الرفض
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                                    @click="showRejectForm = false"
                                >
                                    إلغاء
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-5">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">تفاصيل المقترح</h2>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">القسم</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ proposal.department?.name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">التخصص</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ proposal.specialization?.name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">المشرف</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ proposal.supervisor?.full_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">تاريخ التسليم</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ proposal.submission_date ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">مقدَّم بواسطة</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ proposal.creator?.name ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">نموذج المقترح</h2>
                        <a
                            v-if="proposal.form_file_path"
                            :href="`/storage/${proposal.form_file_path}`"
                            target="_blank"
                            class="flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            ↓ تنزيل النموذج
                        </a>
                        <p v-else class="text-sm text-gray-500">لا يوجد ملف مرفق</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">ملف المقترح</h2>
                        <a
                            v-if="proposal.proposal_file_path"
                            :href="`/storage/${proposal.proposal_file_path}`"
                            target="_blank"
                            class="flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            ↓ تنزيل الملف
                        </a>
                        <p v-else class="text-sm text-gray-500">لا يوجد ملف مرفق</p>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDelete
            :show="showConfirmDelete"
            :item-name="proposal.title"
            @confirmed="deleteProposal"
            @cancelled="showConfirmDelete = false"
        />

        <ProposalFormModal
            :show="showEditModal"
            mode="edit"
            :proposal="proposal"
            :specializations="specializations"
            :supervisors="supervisors"
            @close="showEditModal = false"
            @saved="showEditModal = false"
        />

        <ProposalFormModal
            :show="showReplaceModal"
            mode="replace"
            :proposal="proposal"
            :specializations="specializations"
            :supervisors="supervisors"
            @close="showReplaceModal = false"
            @saved="showReplaceModal = false"
        />
    </AppLayout>
</template>
