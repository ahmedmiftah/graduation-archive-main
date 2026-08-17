<script setup lang="ts">
import LifecycleTimeline from '@/components/LifecycleTimeline.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { proposalStatusColor, proposalStatusLabel } from '@/lib/proposalStatusBadge';
import { type BreadcrumbItem, type Department, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Specialization {
    id: number;
    name: string;
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
    department_note: string | null;
    supervisor_note: string | null;
    department: Department | null;
    specialization: Specialization | null;
    students: ProposalStudent[];
}

const props = defineProps<{
    proposal: Proposal;
    timeline: Stage[];
    canEditNote: boolean;
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'مشاريعي', href: '/supervisor/proposals' },
    { title: props.proposal.title, href: '#' },
]);

const noteForm = useForm({ supervisor_note: props.proposal.supervisor_note ?? '' });

function saveNote() {
    noteForm.patch(route('supervisor.proposals.update-note', props.proposal.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="proposal.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4" dir="rtl">
            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>

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
                <Link
                    :href="route('supervisor.proposals.index')"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    العودة لمشاريعي
                </Link>
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

                    <div v-if="proposal.department_note" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-2 text-base font-semibold text-gray-800 dark:text-gray-200">ملاحظات القسم</h2>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ proposal.department_note }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-gray-200">ملاحظتك كمشرف</h2>
                        <template v-if="canEditNote">
                            <textarea
                                v-model="noteForm.supervisor_note"
                                rows="3"
                                placeholder="أضف ملاحظاتك حول هذا المقترح..."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            />
                            <button
                                type="button"
                                :disabled="noteForm.processing"
                                class="mt-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                @click="saveNote"
                            >
                                {{ noteForm.processing ? 'جارٍ الحفظ...' : 'حفظ الملاحظة' }}
                            </button>
                        </template>
                        <p v-else class="text-sm text-gray-500">{{ proposal.supervisor_note || 'لا توجد ملاحظة' }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">دورة حياة المشروع</h2>
                        <LifecycleTimeline :stages="timeline" />
                    </div>
                </div>

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
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
