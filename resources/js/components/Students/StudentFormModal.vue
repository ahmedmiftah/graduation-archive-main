<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import { type SharedData } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Department {
    id: number;
    name: string;
}
interface Specialization {
    id: number;
    name: string;
    department_id: number;
}

const props = defineProps<{
    show: boolean;
    departments: Department[];
    specializations: Specialization[];
}>();

const emit = defineEmits<{
    close: [];
    saved: [];
}>();

const page = usePage<SharedData>();
const semesters = computed(() => page.props.semesters ?? []);

// dept_manager only ever has a single (their own) department in the list —
// lock the field instead of showing a one-option dropdown.
const departmentLocked = computed(() => props.departments.length === 1);

function emptyForm() {
    return {
        full_name: '',
        national_id: '',
        registration_number: '',
        department_id: props.departments.length === 1 ? props.departments[0].id : (null as number | null),
        specialization_id: null as number | null,
        semester: '',
        academic_year: '',
        date_of_birth: '',
    };
}

const form = useForm(emptyForm());

const availableSpecializations = computed(() => props.specializations.filter((s) => s.department_id === form.department_id));

watch(
    () => props.show,
    (visible) => {
        if (!visible) return;
        Object.assign(form, emptyForm());
        form.clearErrors();
    },
);

watch(
    () => form.department_id,
    () => {
        if (!availableSpecializations.value.some((s) => s.id === form.specialization_id)) {
            form.specialization_id = null;
        }
    },
);

function submit() {
    form.post(route('students.store'), {
        onSuccess: () => emit('saved'),
    });
}

function close() {
    emit('close');
}
</script>

<template>
    <Modal :show="show" title="إضافة طالب فردي" size="lg" @close="close">
        <form id="student-form" class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">اسم الطالب</label>
                <input
                    v-model="form.full_name"
                    type="text"
                    maxlength="255"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    :class="{ 'border-red-500': form.errors.full_name }"
                />
                <p v-if="form.errors.full_name" class="mt-1 text-xs text-red-600">{{ form.errors.full_name }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الرقم الوطني</label>
                    <input
                        v-model="form.national_id"
                        type="text"
                        maxlength="12"
                        placeholder="12 رقماً"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.national_id }"
                    />
                    <p v-if="form.errors.national_id" class="mt-1 text-xs text-red-600">{{ form.errors.national_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">رقم القيد</label>
                    <input
                        v-model="form.registration_number"
                        type="text"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.registration_number }"
                    />
                    <p v-if="form.errors.registration_number" class="mt-1 text-xs text-red-600">{{ form.errors.registration_number }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">القسم</label>
                    <select
                        v-model="form.department_id"
                        :disabled="departmentLocked"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:disabled:bg-gray-800"
                        :class="{ 'border-red-500': form.errors.department_id }"
                    >
                        <option :value="null">اختر القسم</option>
                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                    </select>
                    <p v-if="form.errors.department_id" class="mt-1 text-xs text-red-600">{{ form.errors.department_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">التخصص</label>
                    <select
                        v-model="form.specialization_id"
                        :disabled="!form.department_id"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:disabled:bg-gray-800"
                        :class="{ 'border-red-500': form.errors.specialization_id }"
                    >
                        <option :value="null">اختر التخصص</option>
                        <option v-for="spec in availableSpecializations" :key="spec.id" :value="spec.id">{{ spec.name }}</option>
                    </select>
                    <p v-if="form.errors.specialization_id" class="mt-1 text-xs text-red-600">{{ form.errors.specialization_id }}</p>
                </div>
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
                        maxlength="10"
                        placeholder="مثال: 2025"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.academic_year }"
                    />
                    <p v-if="form.errors.academic_year" class="mt-1 text-xs text-red-600">{{ form.errors.academic_year }}</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">تاريخ الميلاد</label>
                <input
                    v-model="form.date_of_birth"
                    type="date"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    :class="{ 'border-red-500': form.errors.date_of_birth }"
                />
                <p v-if="form.errors.date_of_birth" class="mt-1 text-xs text-red-600">{{ form.errors.date_of_birth }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">تُستخدم كلمة المرور المبدئية لحساب الطالب بصيغة يوم-شهر-سنة الميلاد</p>
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
                form="student-form"
                :disabled="form.processing"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
            >
                {{ form.processing ? 'جاري الحفظ...' : 'حفظ' }}
            </button>
        </template>
    </Modal>
</template>
