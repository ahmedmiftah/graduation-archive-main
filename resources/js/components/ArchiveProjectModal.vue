<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Modal from '@/components/Modal.vue';
import { type SharedData } from '@/types';

interface Department {
    id: number;
    name: string;
}
interface Specialization {
    id: number;
    name: string;
    department_id: number;
}
interface Supervisor {
    id: number;
    full_name: string;
}
interface Examiner {
    id: number;
    full_name: string;
    degree?: { degree_code: string } | null;
}
interface Student {
    full_name: string;
    registration_number: string;
}
interface ExaminerEntry {
    faculty_member_id: number | null;
    notes: string;
}

const props = withDefaults(
    defineProps<{
        show: boolean;
        mode?: 'create' | 'update';
        projectId?: number | null;
        projectTitle: string;
        description: string;
        academicYear: string;
        semester: string;
        degreeLevel: string;
        departmentId: number | null;
        specializationId: number | null;
        supervisorId: number | null;
        students: Student[];
        departments: Department[];
        specializations: Specialization[];
        supervisors: Supervisor[];
        examiners: Examiner[];
        currentFileName?: string | null;
        initialFinalScore?: number | null;
        initialExaminers?: ExaminerEntry[];
    }>(),
    {
        mode: 'create',
        projectId: null,
        currentFileName: null,
        initialFinalScore: null,
        initialExaminers: () => [],
    },
);

const emit = defineEmits<{
    close: [];
    saved: [];
}>();

const page = usePage<SharedData>();
const maxExaminers = computed(() => page.props.systemSettings?.examiners_per_project ?? 2);

const form = useForm({
    pdf_file: null as File | null,
    final_score: props.initialFinalScore,
    examiners: props.initialExaminers.map((e) => ({ ...e })) as ExaminerEntry[],
});

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            form.pdf_file = null;
            form.final_score = props.initialFinalScore;
            form.examiners = props.initialExaminers.map((e) => ({ ...e }));
            form.clearErrors();
        }
    },
);

const departmentName = computed(() => props.departments.find((d) => d.id === props.departmentId)?.name ?? '—');
const specializationName = computed(() => props.specializations.find((s) => s.id === props.specializationId)?.name ?? '—');
const supervisorName = computed(() => props.supervisors.find((s) => s.id === props.supervisorId)?.full_name ?? '—');
const degreeLevelLabel = computed(() => {
    const map: Record<string, string> = { diploma: 'دبلوم', bachelor: 'بكالوريوس', master: 'ماجستير' };
    return map[props.degreeLevel] ?? props.degreeLevel;
});

const gradeLabel = computed(() => {
    const score = form.final_score;
    if (score === null || score === undefined || Number.isNaN(score)) return '—';
    if (score >= 90) return 'ممتاز';
    if (score >= 80) return 'جيد جداً';
    if (score >= 70) return 'جيد';
    if (score >= 60) return 'مقبول';
    return 'ضعيف';
});

function addExaminer() {
    if (form.examiners.length >= maxExaminers.value) return;
    form.examiners.push({ faculty_member_id: null, notes: '' });
}

function removeExaminer(index: number) {
    form.examiners.splice(index, 1);
}

function onFileChange(e: Event) {
    form.pdf_file = (e.target as HTMLInputElement).files?.[0] ?? null;
}

function submit() {
    form.transform((data) => ({
        project_title: props.projectTitle,
        description: props.description,
        degree_level: props.degreeLevel,
        academic_year: props.academicYear,
        semester: props.semester,
        department_id: props.departmentId,
        specialization_id: props.specializationId,
        supervisor_id: props.supervisorId,
        students: props.students,
        pdf_file: data.pdf_file,
        final_score: data.final_score,
        examiners: data.examiners,
        ...(props.mode === 'update' ? { _method: 'PUT' } : {}),
    }));

    const url = props.mode === 'update' ? route('projects.archived.update', [props.projectId]) : route('projects.archived.store');

    form.post(url, {
        forceFormData: true,
        onSuccess: () => emit('saved'),
    });
}

function close() {
    emit('close');
}
</script>

<template>
    <Modal :show="show" title="أرشفة المشروع" size="md" @close="close">
        <div class="space-y-4">
            <!-- Compact read-only summary -->
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ projectTitle || '—' }}</span>
                <span class="mx-1">·</span>{{ departmentName }}
                <span class="mx-1">·</span>{{ specializationName }}
                <span class="mx-1">·</span>{{ academicYear || '—' }} ({{ semester }})
                <span class="mx-1">·</span>{{ students.length }} طالب
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Examiners -->
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            أعضاء هيئة التدريس المناقشون (حد أقصى {{ maxExaminers }})
                        </label>
                        <button
                            v-if="form.examiners.length < maxExaminers"
                            type="button"
                            class="rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 hover:bg-blue-200 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-900/20 dark:text-blue-400"
                            @click="addExaminer"
                        >
                            + إضافة
                        </button>
                    </div>
                    <p v-if="form.errors.examiners" class="mb-1 text-xs text-red-600">{{ form.errors.examiners }}</p>

                    <div class="space-y-2">
                        <div v-for="(examiner, idx) in form.examiners" :key="idx" class="rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <select
                                    v-model="examiner.faculty_member_id"
                                    class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-500': form.errors[`examiners.${idx}.faculty_member_id`] }"
                                >
                                    <option :value="null">اختر عضو هيئة التدريس</option>
                                    <option v-for="ex in props.examiners" :key="ex.id" :value="ex.id">{{ ex.full_name }}</option>
                                </select>
                                <button type="button" class="shrink-0 text-xs text-red-500 hover:text-red-700" @click="removeExaminer(idx)">✕</button>
                            </div>
                            <p v-if="form.errors[`examiners.${idx}.faculty_member_id`]" class="mt-1 text-xs text-red-600">
                                {{ form.errors[`examiners.${idx}.faculty_member_id`] }}
                            </p>
                            <textarea
                                v-model="examiner.notes"
                                rows="1"
                                placeholder="ملاحظات التقييم (اختياري)"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                    </div>
                </div>

                <!-- Final Score + Grade -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">الدرجة النهائية</label>
                        <input
                            v-model.number="form.final_score"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-500': form.errors.final_score }"
                        />
                        <p v-if="form.errors.final_score" class="mt-1 text-xs text-red-600">{{ form.errors.final_score }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">التقدير</label>
                        <div class="mt-1 flex h-[30px] items-center rounded-lg border border-gray-200 bg-gray-50 px-2 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
                            {{ gradeLabel }}
                        </div>
                    </div>
                </div>

                <!-- PDF Upload -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300"> ملف المشروع (PDF) </label>

                    <div
                        v-if="currentFileName && !form.pdf_file"
                        class="mt-1 flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 dark:border-gray-700 dark:bg-gray-700/50"
                    >
                        <span class="text-sm">📄</span>
                        <p class="min-w-0 flex-1 truncate text-xs text-gray-700 dark:text-gray-300">{{ currentFileName }}</p>
                    </div>

                    <input
                        type="file"
                        accept=".pdf"
                        class="mt-1.5 block w-full text-xs text-gray-600 file:ml-2 file:mr-0 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-400 dark:file:bg-blue-900/20 dark:file:text-blue-400"
                        @change="onFileChange"
                    />
                    <p class="mt-1 text-xs text-gray-500">PDF فقط — الحجم الأقصى 15 ميجابايت</p>
                    <p v-if="form.errors.pdf_file" class="mt-1 text-xs text-red-600">{{ form.errors.pdf_file }}</p>
                    <div v-if="form.progress" class="mt-1.5">
                        <div class="h-1 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-1 rounded-full bg-blue-600 transition-all duration-300" :style="{ width: `${form.progress.percentage}%` }" />
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'جاري الحفظ...' : mode === 'update' ? 'حفظ تعديلات الأرشفة' : 'حفظ المشروع المؤرشف' }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        @click="close"
                    >
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
