<script setup lang="ts">
import LifecycleTimeline from '@/components/LifecycleTimeline.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { proposalStatusColor, proposalStatusLabel } from '@/lib/proposalStatusBadge';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Stage {
    key: string;
    name_ar: string;
    sort_order: number;
    status: 'completed' | 'current' | 'upcoming';
    needs_student_action: boolean;
}

interface StudentInfo {
    id: number;
    full_name: string;
    registration_number: string;
    department: { id: number; name: string };
    specialization: { id: number; name: string };
    user: { is_active: boolean } | null;
}

interface Proposal {
    id: number;
    title: string;
    status: string;
    semester: string;
    academic_year: string;
    supervisor: { id: number; full_name: string } | null;
}

const props = defineProps<{
    student: StudentInfo;
    proposal: Proposal | null;
    timeline: Stage[] | null;
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs: BreadcrumbItem[] = [{ title: 'لوحة التحكم', href: '/student/dashboard' }];

const canEditProposal = computed(() => props.proposal && ['pending', 'needs_revision'].includes(props.proposal.status));
</script>

<template>
    <Head title="لوحة التحكم" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4" dir="rtl">
            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                {{ flash.error }}
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">مرحباً {{ student.full_name }}</h1>
                <dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">رقم القيد</dt>
                        <dd class="mt-0.5 font-mono text-sm font-medium text-gray-800 dark:text-gray-200">{{ student.registration_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">القسم</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ student.department.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">التخصص</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ student.specialization.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">حالة الطالب</dt>
                        <dd class="mt-0.5">
                            <span
                                :class="
                                    student.user?.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'
                                "
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                            >
                                {{ student.user?.is_active ? 'نشط' : 'غير نشط' }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- No proposal yet -->
            <div v-if="!proposal" class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800">
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">لم تُقدّم مقترح مشروع تخرج بعد.</p>
                <Link
                    :href="route('student.proposal.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700"
                >
                    تقديم مقترح جديد
                </Link>
            </div>

            <!-- Current proposal -->
            <template v-else>
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">مشروع التخرج</p>
                            <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ proposal.title }}</h2>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', proposalStatusColor(proposal.status)]">
                                    {{ proposalStatusLabel(proposal.status) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ proposal.semester }} {{ proposal.academic_year }}</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Link
                                :href="route('student.proposal.show', proposal.id)"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                عرض التفاصيل
                            </Link>
                            <Link
                                v-if="canEditProposal"
                                :href="route('student.proposal.edit', proposal.id)"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                تعديل
                            </Link>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        المشرف: <span class="font-medium text-gray-800 dark:text-gray-200">{{ proposal.supervisor?.full_name ?? 'لم يُحدَّد بعد' }}</span>
                    </p>
                </div>

                <div v-if="timeline" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">دورة حياة المشروع</h2>
                    <LifecycleTimeline :stages="timeline" />
                </div>
            </template>
        </div>
    </AppLayout>
</template>
