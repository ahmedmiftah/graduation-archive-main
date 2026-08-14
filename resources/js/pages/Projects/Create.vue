<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
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
interface Supervisor {
    id: number;
    name: string;
    department_id: number;
}

interface Student {
    full_name: string;
    registration_number: string;
}

const props = defineProps<{
    departments: Department[];
    specializations: Specialization[];
    supervisors: Supervisor[];
}>();

// New projects always start "in progress" — the archive workflow (examiners,
// score, file) happens later from the project's edit page.
const STATUS_IN_PROGRESS = 5;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'المشاريع', href: '/projects' },
    { title: 'إضافة مشروع', href: '/projects/create' },
];

const form = useForm({
    project_title: '',
    description: '',
    academic_year: '',
    semester: 'خريف',
    degree_level: 'bachelor',
    department_id: null as number | null,
    specialization_id: null as number | null,
    supervisor_id: null as number | null,
    current_status_id: STATUS_IN_PROGRESS as number,
    students: [{ full_name: '', registration_number: '' }] as Student[],
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const filteredDepartments = computed(() => {
    if (authUser.value?.role === 'super_admin' || !authUser.value?.department_id) {
        return props.departments;
    }
    return props.departments.filter((d) => d.id === authUser.value?.department_id);
});

const filteredSpecializations = computed(() =>
    form.department_id ? props.specializations.filter((s) => Number(s.department_id) === Number(form.department_id)) : [],
);

const filteredSupervisors = computed(() =>
    form.department_id ? props.supervisors.filter((s) => Number(s.department_id) === Number(form.department_id)) : [],
);

watch(
    () => form.department_id,
    () => {
        form.specialization_id = null;
        form.supervisor_id = null;
    },
);

if (authUser.value?.department_id) {
    form.department_id = authUser.value.department_id;
}

function addStudent() {
    form.students.push({ full_name: '', registration_number: '' });
}

function removeStudent(index: number) {
    if (form.students.length > 1) form.students.splice(index, 1);
}

function submit() {
    form.post(route('projects.store'));
}
</script>

<template>
    <Head title="إضافة مشروع" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">إضافة مشروع جديد</h1>

            <div class="max-w-3xl rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            عنوان المشروع <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.project_title"
                            type="text"
                            maxlength="255"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-500': form.errors.project_title }"
                        />
                        <p v-if="form.errors.project_title" class="mt-1 text-xs text-red-600">{{ form.errors.project_title }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"> الوصف <span class="text-red-500">*</span> </label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-500': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <!-- Academic Year -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            السنة الدراسية <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.academic_year"
                            type="text"
                            maxlength="20"
                            placeholder="مثال: 2024-2025"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-500': form.errors.academic_year }"
                        />
                        <p v-if="form.errors.academic_year" class="mt-1 text-xs text-red-600">{{ form.errors.academic_year }}</p>
                    </div>

                    <!-- Semester -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الفصل الدراسي <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.semester"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-500': form.errors.semester }"
                        >
                            <option value="خريف">خريف</option>
                            <option value="ربيع">ربيع</option>
                        </select>
                        <p v-if="form.errors.semester" class="mt-1 text-xs text-red-600">{{ form.errors.semester }}</p>
                    </div>

                    <!-- Degree Level -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الفئة (الدرجة العلمية) <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.degree_level"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-500': form.errors.degree_level }"
                        >
                            <option value="diploma">دبلوم</option>
                            <option value="bachelor">بكالوريوس</option>
                            <option value="master">ماجستير</option>
                        </select>
                        <p v-if="form.errors.degree_level" class="mt-1 text-xs text-red-600">{{ form.errors.degree_level }}</p>
                    </div>

                    <!-- Department + Specialization -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                القسم <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.department_id"
                                :disabled="!!authUser?.department_id && authUser?.role !== 'super_admin'"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:disabled:bg-gray-800"
                                :class="{ 'border-red-500': form.errors.department_id }"
                            >
                                <option :value="null">اختر القسم</option>
                                <option v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                            </select>
                            <p v-if="form.errors.department_id" class="mt-1 text-xs text-red-600">{{ form.errors.department_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                التخصص <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.specialization_id"
                                :disabled="!form.department_id"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:disabled:bg-gray-800"
                                :class="{ 'border-red-500': form.errors.specialization_id }"
                            >
                                <option :value="null">{{ form.department_id ? 'اختر التخصص' : 'اختر القسم أولاً' }}</option>
                                <option v-for="spec in filteredSpecializations" :key="spec.id" :value="spec.id">{{ spec.name }}</option>
                            </select>
                            <p v-if="form.errors.specialization_id" class="mt-1 text-xs text-red-600">{{ form.errors.specialization_id }}</p>
                        </div>
                    </div>

                    <!-- Supervisor -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"> المشرف <span class="text-red-500">*</span> </label>
                        <select
                            v-model="form.supervisor_id"
                            :disabled="!form.department_id"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:disabled:bg-gray-800"
                            :class="{ 'border-red-500': form.errors.supervisor_id }"
                        >
                            <option :value="null">{{ form.department_id ? 'اختر المشرف' : 'اختر القسم أولاً' }}</option>
                            <option v-for="sup in filteredSupervisors" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                        </select>
                        <p v-if="form.errors.supervisor_id" class="mt-1 text-xs text-red-600">{{ form.errors.supervisor_id }}</p>
                    </div>

                    <!-- Students -->
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                الطلاب <span class="text-red-500">*</span>
                            </label>
                            <button
                                type="button"
                                class="rounded bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-200 dark:bg-blue-900/20 dark:text-blue-400"
                                @click="addStudent"
                            >
                                + إضافة طالب
                            </button>
                        </div>
                        <p v-if="form.errors.students" class="mb-2 text-xs text-red-600">{{ form.errors.students }}</p>

                        <div class="space-y-3">
                            <div
                                v-for="(student, idx) in form.students"
                                :key="idx"
                                class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                            >
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
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">الاسم الكامل</label>
                                        <input
                                            v-model="student.full_name"
                                            type="text"
                                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                            :class="{ 'border-red-500': form.errors[`students.${idx}.full_name`] }"
                                        />
                                        <p v-if="form.errors[`students.${idx}.full_name`]" class="mt-1 text-xs text-red-600">
                                            {{ form.errors[`students.${idx}.full_name`] }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">رقم القيد</label>
                                        <input
                                            v-model="student.registration_number"
                                            type="text"
                                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                            :class="{ 'border-red-500': form.errors[`students.${idx}.registration_number`] }"
                                        />
                                        <p v-if="form.errors[`students.${idx}.registration_number`]" class="mt-1 text-xs text-red-600">
                                            {{ form.errors[`students.${idx}.registration_number`] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'جاري الحفظ...' : 'حفظ المشروع' }}
                        </button>
                        <a
                            :href="route('projects.index')"
                            class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
