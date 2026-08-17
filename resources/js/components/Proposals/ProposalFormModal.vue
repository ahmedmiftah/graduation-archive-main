<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import { type SharedData } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Specialization {
    id: number;
    name: string;
    department_id: number;
}
interface Supervisor {
    id: number;
    full_name: string;
}
interface StudentEntry {
    full_name: string;
    registration_number: string;
    phone_number: string;
}
interface ProposalStudent {
    id: number;
    full_name: string;
    registration_number: string;
    phone_number: string | null;
}
interface Proposal {
    id: number;
    title: string;
    description: string;
    specialization_id: number;
    semester: string;
    academic_year: string;
    submission_date: string | null;
    supervisor_id: number | null;
    students: ProposalStudent[];
}

const props = withDefaults(
    defineProps<{
        show: boolean;
        mode?: 'create' | 'edit' | 'replace';
        proposal?: Proposal | null;
        specializations: Specialization[];
        supervisors: Supervisor[];
        storeRouteName?: string;
        updateRouteName?: string;
        defaultStudent?: { full_name: string; registration_number: string } | null;
        defaults?: { specialization_id?: number; academic_year?: string; semester?: string } | null;
    }>(),
    {
        mode: 'create',
        proposal: null,
        storeRouteName: 'proposals.store',
        updateRouteName: 'proposals.update',
        defaultStudent: null,
        defaults: null,
    },
);

const emit = defineEmits<{
    close: [];
    saved: [];
}>();

const page = usePage<SharedData>();
const semesters = computed(() => page.props.semesters ?? []);
const maxStudents = computed(() => page.props.systemSettings?.max_students_per_project ?? 5);

function todayIso(): string {
    return new Date().toISOString().split('T')[0];
}

function emptyStudent(): StudentEntry {
    return { full_name: '', registration_number: '', phone_number: '' };
}

function initialStudents(): StudentEntry[] {
    if (props.proposal?.students?.length) {
        return props.proposal.students.map((s) => ({
            full_name: s.full_name,
            registration_number: s.registration_number,
            phone_number: s.phone_number ?? '',
        }));
    }
    if (props.defaultStudent) {
        return [{ full_name: props.defaultStudent.full_name, registration_number: props.defaultStudent.registration_number, phone_number: '' }];
    }
    return [emptyStudent()];
}

const form = useForm({
    title: props.proposal?.title ?? '',
    description: props.proposal?.description ?? '',
    specialization_id: props.proposal?.specialization_id ?? props.defaults?.specialization_id ?? (null as number | null),
    students: initialStudents(),
    semester: props.proposal?.semester ?? props.defaults?.semester ?? '',
    academic_year: props.proposal?.academic_year ?? props.defaults?.academic_year ?? '',
    submission_date: props.mode === 'replace' ? todayIso() : (props.proposal?.submission_date ?? todayIso()),
    supervisor_id: props.proposal?.supervisor_id ?? (null as number | null),
    form_file: null as File | null,
    proposal_file: null as File | null,
    replaces_proposal_id: props.mode === 'replace' ? (props.proposal?.id ?? null) : null,
});

watch(
    () => props.show,
    (visible) => {
        if (!visible) return;
        form.title = props.proposal?.title ?? '';
        form.description = props.proposal?.description ?? '';
        form.specialization_id = props.proposal?.specialization_id ?? props.defaults?.specialization_id ?? null;
        form.students = initialStudents();
        form.semester = props.proposal?.semester ?? props.defaults?.semester ?? '';
        form.academic_year = props.proposal?.academic_year ?? props.defaults?.academic_year ?? '';
        form.submission_date = props.mode === 'replace' ? todayIso() : (props.proposal?.submission_date ?? todayIso());
        form.supervisor_id = props.proposal?.supervisor_id ?? null;
        form.form_file = null;
        form.proposal_file = null;
        form.replaces_proposal_id = props.mode === 'replace' ? (props.proposal?.id ?? null) : null;
        form.clearErrors();
    },
);

const title = computed(() => {
    if (props.mode === 'edit') return 'تعديل المقترح';
    if (props.mode === 'replace') return 'إضافة مقترح بديل';
    return 'إضافة مقترح جديد';
});

function addStudent() {
    if (form.students.length >= maxStudents.value) return;
    form.students.push(emptyStudent());
}

function removeStudent(index: number) {
    if (form.students.length > 1) form.students.splice(index, 1);
}

function onFormFileChange(e: Event) {
    form.form_file = (e.target as HTMLInputElement).files?.[0] ?? null;
}

function onProposalFileChange(e: Event) {
    form.proposal_file = (e.target as HTMLInputElement).files?.[0] ?? null;
}

function submit() {
    const options = {
        forceFormData: true,
        onSuccess: () => emit('saved'),
    };

    if (props.mode === 'edit' && props.proposal) {
        form.transform((data) => ({ ...data, _method: 'PUT' })).post(route(props.updateRouteName, [props.proposal!.id]), options);
    } else {
        form.post(route(props.storeRouteName), options);
    }
}

function close() {
    emit('close');
}
</script>

<template>
    <Modal :show="show" :title="title" size="lg" @close="close">
        <form id="proposal-form" class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">عنوان المشروع</label>
                <input
                    v-model="form.title"
                    type="text"
                    maxlength="255"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    :class="{ 'border-red-500': form.errors.title }"
                />
                <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">وصف المشروع</label>
                <textarea
                    v-model="form.description"
                    rows="3"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    :class="{ 'border-red-500': form.errors.description }"
                />
                <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">التخصص</label>
                <select
                    v-model="form.specialization_id"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    :class="{ 'border-red-500': form.errors.specialization_id }"
                >
                    <option :value="null">اختر التخصص</option>
                    <option v-for="spec in specializations" :key="spec.id" :value="spec.id">{{ spec.name }}</option>
                </select>
                <p v-if="form.errors.specialization_id" class="mt-1 text-xs text-red-600">{{ form.errors.specialization_id }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الفصل الدراسي</label>
                    <select
                        v-model="form.semester"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.semester }"
                    >
                        <option value="" disabled>اختر الفصل</option>
                        <option v-for="sem in semesters" :key="sem" :value="sem">{{ sem }}</option>
                    </select>
                    <p v-if="form.errors.semester" class="mt-1 text-xs text-red-600">{{ form.errors.semester }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">السنة الدراسية</label>
                    <input
                        v-model="form.academic_year"
                        type="text"
                        maxlength="4"
                        placeholder="مثال: 2025"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.academic_year }"
                    />
                    <p v-if="form.errors.academic_year" class="mt-1 text-xs text-red-600">{{ form.errors.academic_year }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">تاريخ التسليم</label>
                    <input
                        v-model="form.submission_date"
                        type="date"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.submission_date }"
                    />
                    <p v-if="form.errors.submission_date" class="mt-1 text-xs text-red-600">{{ form.errors.submission_date }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">المشرف</label>
                    <select
                        v-model="form.supervisor_id"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.supervisor_id }"
                    >
                        <option :value="null">بدون مشرف (اختياري)</option>
                        <option v-for="sup in supervisors" :key="sup.id" :value="sup.id">{{ sup.full_name }}</option>
                    </select>
                    <p v-if="form.errors.supervisor_id" class="mt-1 text-xs text-red-600">{{ form.errors.supervisor_id }}</p>
                </div>
            </div>

            <!-- Students -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        بيانات الطلبة <span class="text-xs font-normal text-gray-400">(الحد الأقصى {{ maxStudents }})</span>
                    </label>
                    <button
                        v-if="form.students.length < maxStudents"
                        type="button"
                        class="rounded bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-200 dark:bg-blue-900/20 dark:text-blue-400"
                        @click="addStudent"
                    >
                        + إضافة طالب
                    </button>
                </div>
                <p v-if="form.errors.students" class="mb-2 text-xs text-red-600">{{ form.errors.students }}</p>

                <div class="space-y-3">
                    <div v-for="(student, idx) in form.students" :key="idx" class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">طالب {{ idx + 1 }}</span>
                            <button
                                v-if="form.students.length > 1"
                                type="button"
                                class="text-xs text-red-500 hover:text-red-700"
                                @click="removeStudent(idx)"
                            >
                                ✕ حذف
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">الاسم</label>
                                <input
                                    v-model="student.full_name"
                                    type="text"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-500': form.errors[`students.${idx}.full_name`] }"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">رقم القيد</label>
                                <input
                                    v-model="student.registration_number"
                                    type="text"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-500': form.errors[`students.${idx}.registration_number`] }"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">رقم الهاتف</label>
                                <input
                                    v-model="student.phone_number"
                                    type="text"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attachments -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">نموذج المقترح (PDF)</label>
                    <input
                        type="file"
                        accept=".pdf"
                        class="mt-1.5 block w-full text-xs text-gray-600 file:ml-2 file:mr-0 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-400 dark:file:bg-blue-900/20 dark:file:text-blue-400"
                        @change="onFormFileChange"
                    />
                    <p v-if="form.errors.form_file" class="mt-1 text-xs text-red-600">{{ form.errors.form_file }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ملف المقترح (PDF)</label>
                    <input
                        type="file"
                        accept=".pdf"
                        class="mt-1.5 block w-full text-xs text-gray-600 file:ml-2 file:mr-0 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-400 dark:file:bg-blue-900/20 dark:file:text-blue-400"
                        @change="onProposalFileChange"
                    />
                    <p v-if="form.errors.proposal_file" class="mt-1 text-xs text-red-600">{{ form.errors.proposal_file }}</p>
                </div>
            </div>

            <div v-if="form.progress" class="mt-1.5">
                <div class="h-1 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-1 rounded-full bg-blue-600 transition-all duration-300" :style="{ width: `${form.progress.percentage}%` }" />
                </div>
            </div>
        </form>

        <template #footer>
            <button
                type="button"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                @click="close"
            >
                إلغاء
            </button>
            <button
                type="submit"
                form="proposal-form"
                :disabled="form.processing"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
            >
                {{ form.processing ? 'جاري الحفظ...' : 'حفظ' }}
            </button>
        </template>
    </Modal>
</template>
