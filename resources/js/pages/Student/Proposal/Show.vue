<script setup lang="ts">
import LifecycleTimeline from '@/components/LifecycleTimeline.vue';
import SimilarityWarning from '@/components/SimilarityWarning.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { proposalStatusColor, proposalStatusLabel } from '@/lib/proposalStatusBadge';
import { type BreadcrumbItem, type Department, type SharedData, type SimilarProject } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Specialization {
    id: number;
    name: string;
    department_id: number;
}
interface Supervisor {
    id: number;
    full_name: string;
    phone_number: string | null;
    email: string | null;
}
interface ProposalStudent {
    id: number;
    full_name: string;
    registration_number: string;
    phone_number: string | null;
}
interface Stage {
    key: string;
    name_ar: string;
    sort_order: number;
    status: 'completed' | 'current' | 'upcoming';
    needs_student_action: boolean;
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
    department: Department | null;
    specialization: Specialization | null;
    supervisor: Supervisor | null;
    students: ProposalStudent[];
}

const props = defineProps<{
    proposal: Proposal;
    timeline: Stage[];
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});
const dismissedWarning = ref(false);
const similarProjects = computed<SimilarProject[]>(() => (dismissedWarning.value ? [] : (flash.value.similarity_warning ?? [])));

const canEdit = computed(() => ['pending', 'needs_revision'].includes(props.proposal.status));

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'لوحة التحكم', href: '/student/dashboard' },
    { title: props.proposal.title, href: '#' },
]);
</script>

<template>
    <Head :title="proposal.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4" dir="rtl">
            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>

            <SimilarityWarning
                :show="similarProjects.length > 0"
                :similar-projects="similarProjects"
                @continue="dismissedWarning = true"
                @change-title="dismissedWarning = true"
            />

            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ proposal.title }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ proposal.semester }} {{ proposal.academic_year }}</span>
                        <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', proposalStatusColor(proposal.status)]">
                            {{ proposalStatusLabel(proposal.status) }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link
                        v-if="canEdit"
                        :href="route('student.proposal.edit', proposal.id)"
                        class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-600"
                    >
                        تعديل
                    </Link>
                    <Link
                        :href="route('student.dashboard')"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        العودة للوحة التحكم
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-gray-200">الوصف</h2>
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ proposal.description }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-gray-200">
                            فريق المشروع <span class="text-sm font-normal text-gray-400">({{ proposal.students.length }})</span>
                        </h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">الاسم</th>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">رقم القيد</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-transparent">
                                    <tr v-for="s in proposal.students" :key="s.id">
                                        <td class="px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ s.full_name }}</td>
                                        <td class="px-4 py-2 font-mono text-sm text-gray-600 dark:text-gray-400">{{ s.registration_number }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div
                        v-if="proposal.status === 'rejected' && proposal.rejection_reason"
                        class="rounded-xl border border-red-200 bg-red-50 p-5 dark:border-red-900/40 dark:bg-red-900/10"
                    >
                        <h2 class="mb-2 text-base font-semibold text-red-800 dark:text-red-400">سبب الرفض</h2>
                        <p class="text-sm text-red-700 dark:text-red-400">{{ proposal.rejection_reason }}</p>
                    </div>

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

                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">دورة حياة المشروع</h2>
                        <LifecycleTimeline :stages="timeline" />
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">المشرف</h2>
                        <template v-if="proposal.supervisor">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ proposal.supervisor.full_name }}</p>
                            <p v-if="proposal.supervisor.phone_number" class="mt-1 text-xs text-gray-500">{{ proposal.supervisor.phone_number }}</p>
                            <p v-if="proposal.supervisor.email" class="text-xs text-gray-500">{{ proposal.supervisor.email }}</p>
                        </template>
                        <p v-else class="text-sm text-gray-500">لم يُحدَّد مشرف بعد</p>
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
    </AppLayout>
</template>
