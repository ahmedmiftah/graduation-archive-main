<script setup lang="ts">
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import Modal from '@/components/Modal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Semester, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

interface Settings {
    id: number;
    max_students_per_project: number;
    examiners_per_project: number;
    max_projects_per_supervisor_per_semester: number;
    registration_number_length: number;
    academic_year_format: '2_digit' | '4_digit';
    archive_enabled: boolean;
    archivable_proposal_statuses: string[];
}

const props = defineProps<{
    settings: Settings;
    semesters: Semester[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'إعدادات النظام', href: '/settings/system' },
];

// ── Main settings form ──────────────────────────────────────────────
const form = useForm({
    max_students_per_project: props.settings.max_students_per_project,
    examiners_per_project: props.settings.examiners_per_project,
    max_projects_per_supervisor_per_semester: props.settings.max_projects_per_supervisor_per_semester,
    registration_number_length: props.settings.registration_number_length,
    academic_year_format: props.settings.academic_year_format,
    archive_enabled: props.settings.archive_enabled,
    archivable_proposal_statuses: [...props.settings.archivable_proposal_statuses],
});

function toggleArchivableStatus(status: string) {
    const idx = form.archivable_proposal_statuses.indexOf(status);
    if (idx === -1) {
        form.archivable_proposal_statuses.push(status);
    } else {
        form.archivable_proposal_statuses.splice(idx, 1);
    }
}

function submitSettings() {
    form.put(route('settings.system.update'));
}

// ── Semesters ─────────────────────────────────────────────────────
const showSemesterModal = ref(false);
const semesterForm = useForm({ name: '' });

function openAddSemester() {
    semesterForm.reset();
    showSemesterModal.value = true;
}

function submitSemester() {
    semesterForm.post(route('settings.semesters.store'), {
        onSuccess: () => {
            showSemesterModal.value = false;
            semesterForm.reset();
        },
    });
}

const confirmDeleteSemester = ref<Semester | null>(null);

function deleteSemester() {
    if (!confirmDeleteSemester.value) return;
    router.delete(route('settings.semesters.destroy', [confirmDeleteSemester.value.id]), {
        onFinish: () => (confirmDeleteSemester.value = null),
    });
}
</script>

<template>
    <Head title="إعدادات النظام" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">إعدادات النظام</h1>

            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                {{ flash.error }}
            </div>

            <form class="space-y-6" @submit.prevent="submitSettings">
                <!-- Project settings -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">إعدادات المشاريع</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الحد الأقصى لعدد الطلاب في المشروع</label>
                            <input
                                v-model.number="form.max_students_per_project"
                                type="number"
                                min="1"
                                max="20"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'border-red-500': form.errors.max_students_per_project }"
                            />
                            <p v-if="form.errors.max_students_per_project" class="mt-1 text-xs text-red-600">
                                {{ form.errors.max_students_per_project }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">عدد الممتحنين لكل مشروع</label>
                            <input
                                v-model.number="form.examiners_per_project"
                                type="number"
                                min="1"
                                max="10"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'border-red-500': form.errors.examiners_per_project }"
                            />
                            <p v-if="form.errors.examiners_per_project" class="mt-1 text-xs text-red-600">{{ form.errors.examiners_per_project }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                الحد الأقصى لعدد المشاريع للمشرف في الفصل الدراسي الواحد
                            </label>
                            <input
                                v-model.number="form.max_projects_per_supervisor_per_semester"
                                type="number"
                                min="1"
                                max="50"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'border-red-500': form.errors.max_projects_per_supervisor_per_semester }"
                            />
                            <p v-if="form.errors.max_projects_per_supervisor_per_semester" class="mt-1 text-xs text-red-600">
                                {{ form.errors.max_projects_per_supervisor_per_semester }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">طول رقم القيد (لاستيراد الطلاب)</label>
                            <input
                                v-model.number="form.registration_number_length"
                                type="number"
                                min="4"
                                max="20"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'border-red-500': form.errors.registration_number_length }"
                            />
                            <p v-if="form.errors.registration_number_length" class="mt-1 text-xs text-red-600">
                                {{ form.errors.registration_number_length }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Year format -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">صيغة سنة الفصل الدراسي (للمقترحات)</h2>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input v-model="form.academic_year_format" type="radio" value="2_digit" />
                            رقمان (مثال: 25)
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input v-model="form.academic_year_format" type="radio" value="4_digit" />
                            أربعة أرقام (مثال: 2025)
                        </label>
                    </div>
                </div>

                <!-- Archive settings -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">إعدادات الأرشفة</h2>
                    <label class="mb-3 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input v-model="form.archive_enabled" type="checkbox" />
                        تفعيل أرشيف المقترحات
                    </label>
                    <div v-if="form.archive_enabled" class="space-y-2">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">الحالات المسموح بأرشفتها:</p>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                :checked="form.archivable_proposal_statuses.includes('approved')"
                                @change="toggleArchivableStatus('approved')"
                            />
                            المقترحات المعتمدة
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                :checked="form.archivable_proposal_statuses.includes('rejected')"
                                @change="toggleArchivableStatus('rejected')"
                            />
                            المقترحات المرفوضة
                        </label>
                        <p v-if="form.errors.archivable_proposal_statuses" class="text-xs text-red-600">
                            {{ form.errors.archivable_proposal_statuses }}
                        </p>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    حفظ الإعدادات
                </button>
            </form>

            <!-- Semesters -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200">الفصول الدراسية</h2>
                    <button
                        type="button"
                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700"
                        @click="openAddSemester"
                    >
                        + إضافة فصل دراسي
                    </button>
                </div>
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الاسم</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="semester in props.semesters" :key="semester.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ semester.name }}</td>
                                <td class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="rounded bg-red-100 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400"
                                        @click="confirmDeleteSemester = semester"
                                    >
                                        حذف
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="props.semesters.length === 0">
                                <td colspan="2" class="px-4 py-10 text-center text-sm text-gray-500">لا توجد فصول دراسية مسجلة</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add semester modal -->
        <Modal :show="showSemesterModal" title="إضافة فصل دراسي جديد" size="md" @close="showSemesterModal = false">
            <form id="semester-form" @submit.prevent="submitSemester">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">اسم الفصل الدراسي</label>
                <input
                    v-model="semesterForm.name"
                    type="text"
                    maxlength="50"
                    placeholder="مثال: صيفي"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    :class="{ 'border-red-500': semesterForm.errors.name }"
                />
                <p v-if="semesterForm.errors.name" class="mt-1 text-xs text-red-600">{{ semesterForm.errors.name }}</p>
            </form>
            <template #footer>
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    @click="showSemesterModal = false"
                >
                    إلغاء
                </button>
                <button
                    type="submit"
                    form="semester-form"
                    :disabled="semesterForm.processing"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    حفظ
                </button>
            </template>
        </Modal>

        <ConfirmDelete
            :show="!!confirmDeleteSemester"
            :item-name="confirmDeleteSemester?.name"
            @confirmed="deleteSemester"
            @cancelled="confirmDeleteSemester = null"
        />
    </AppLayout>
</template>
