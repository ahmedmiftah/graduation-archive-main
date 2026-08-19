<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import StudentFormModal from '@/components/Students/StudentFormModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// ── Types ────────────────────────────────────────────────────────────────────

interface Department {
    id: number;
    name: string;
}

interface Specialization {
    id: number;
    name: string;
    department_id: number;
}

interface StudentRow {
    id: number;
    full_name: string;
    registration_number: string;
    masked_national_id: string;
    department: string;
    specialization: string;
    semester: string;
    academic_year: string;
    account_active: boolean;
    account_created_at: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedStudents {
    data: StudentRow[];
    links: PaginationLink[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
}

const props = defineProps<{
    students: PaginatedStudents;
    specializations: Specialization[];
    departments: Department[];
    filters: {
        search?: string;
        specialization_id?: string;
        semester?: string;
        academic_year?: string;
        account_status?: string;
    };
}>();

const page = usePage<SharedData & { flash: { reset_password_value?: string } }>();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'الطلاب', href: '/students' },
];

// ── Filters ──────────────────────────────────────────────────────────────────

const search = ref(props.filters.search ?? '');
const selectedSpecialization = ref(props.filters.specialization_id ?? '');
const selectedSemester = ref(props.filters.semester ?? '');
const selectedYear = ref(props.filters.academic_year ?? '');
const selectedAccountStatus = ref(props.filters.account_status ?? '');

let searchTimer: ReturnType<typeof setTimeout>;

function applyFilters() {
    const params: Record<string, string> = {};
    if (search.value) params.search = search.value;
    if (selectedSpecialization.value) params.specialization_id = selectedSpecialization.value;
    if (selectedSemester.value) params.semester = selectedSemester.value;
    if (selectedYear.value) params.academic_year = selectedYear.value;
    if (selectedAccountStatus.value !== '') params.account_status = selectedAccountStatus.value;
    router.get(route('students.index'), params, { preserveScroll: true, replace: true });
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
}

function resetFilters() {
    search.value = '';
    selectedSpecialization.value = '';
    selectedSemester.value = '';
    selectedYear.value = '';
    selectedAccountStatus.value = '';
    router.get(route('students.index'), {}, { preserveScroll: false, replace: true });
}

const hasFilters = computed(
    () => !!(search.value || selectedSpecialization.value || selectedSemester.value || selectedYear.value || selectedAccountStatus.value),
);

const rowNumber = (idx: number) => (props.students.meta.current_page - 1) * props.students.meta.per_page + idx + 1;

// ── Actions ──────────────────────────────────────────────────────────────────

function toggleActive(student: StudentRow) {
    router.patch(route('students.toggle-active', student.id), {}, { preserveScroll: true });
}

const showResetConfirm = ref<StudentRow | null>(null);
const showResetResult = ref(false);

function confirmReset(student: StudentRow) {
    showResetConfirm.value = student;
}

function doResetPassword() {
    if (!showResetConfirm.value) return;
    router.post(
        route('students.reset-password', showResetConfirm.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showResetConfirm.value = null;
                showResetResult.value = true;
            },
        },
    );
}

const showAddModal = ref(false);
</script>

<template>
    <Head title="الطلاب" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">الطلاب</h1>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-blue-600 px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-900/20"
                        @click="showAddModal = true"
                    >
                        + إضافة طالب فردي
                    </button>
                    <Link
                        :href="route('students.import.index')"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        + استيراد طلاب
                    </Link>
                </div>
            </div>

            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                {{ flash.error }}
            </div>

            <!-- ── Filter bar ─────────────────────────────────── -->
            <div class="flex flex-wrap items-center gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="البحث بالاسم أو رقم القيد..."
                    class="min-w-60 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    @input="onSearchInput"
                />
                <select
                    v-model="selectedSpecialization"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    @change="applyFilters"
                >
                    <option value="">جميع التخصصات</option>
                    <option v-for="s in specializations" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <input
                    v-model="selectedSemester"
                    type="text"
                    placeholder="الفصل الدراسي"
                    class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    @change="applyFilters"
                />
                <input
                    v-model="selectedYear"
                    type="text"
                    placeholder="السنة الدراسية"
                    class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    @change="applyFilters"
                />
                <select
                    v-model="selectedAccountStatus"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    @change="applyFilters"
                >
                    <option value="">جميع حالات الحساب</option>
                    <option value="active">نشط</option>
                    <option value="disabled">غير نشط</option>
                </select>
                <button v-if="hasFilters" type="button" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" @click="resetFilters">
                    ✕ مسح الفلاتر
                </button>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">إجمالي الطلاب: {{ students.meta.total }}</p>

            <!-- ── Table ──────────────────────────────────────── -->
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="w-12 px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">اسم الطالب</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">رقم القيد</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الرقم الوطني</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">القسم</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">التخصص</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الفصل / السنة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">حالة الحساب</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">حالة المشروع</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">تاريخ إنشاء الحساب</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr v-for="(student, idx) in students.data" :key="student.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500">{{ rowNumber(idx) }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ student.full_name }}</td>
                            <td class="px-4 py-3 font-mono text-sm text-gray-600 dark:text-gray-400">{{ student.registration_number }}</td>
                            <td class="px-4 py-3 font-mono text-sm text-gray-600 dark:text-gray-400">{{ student.masked_national_id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ student.department }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ student.specialization }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ student.semester }} {{ student.academic_year }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    :class="
                                        student.account_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'
                                    "
                                    class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                >
                                    {{ student.account_active ? 'نشط' : 'غير نشط' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500">لم يبدأ المشروع بعد</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ student.account_created_at ? new Date(student.account_created_at).toLocaleDateString('ar-EG') : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        :href="route('students.show', student.id)"
                                        class="rounded bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-200 dark:bg-blue-900/20 dark:text-blue-400"
                                    >
                                        عرض
                                    </Link>
                                    <button
                                        type="button"
                                        :class="
                                            student.account_active
                                                ? 'bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900/20 dark:text-orange-400'
                                                : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/20 dark:text-green-400'
                                        "
                                        class="rounded px-3 py-1 text-xs font-medium"
                                        @click="toggleActive(student)"
                                    >
                                        {{ student.account_active ? 'تعطيل' : 'تفعيل' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700 hover:bg-amber-200 dark:bg-amber-900/20 dark:text-amber-400"
                                        @click="confirmReset(student)"
                                    >
                                        إعادة تعيين كلمة المرور
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="students.data.length === 0">
                            <td colspan="11" class="px-4 py-10 text-center text-sm text-gray-500">لا يوجد طلاب مستوردون</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── Pagination ──────────────────────────────────── -->
            <div v-if="students.meta.last_page > 1" class="flex items-center justify-between text-sm">
                <p class="text-gray-600 dark:text-gray-400">صفحة {{ students.meta.current_page }} من {{ students.meta.last_page }}</p>
                <div class="flex gap-1">
                    <template v-for="link in students.links" :key="link.label">
                        <button
                            v-if="link.url"
                            type="button"
                            :class="[
                                'min-w-8 rounded px-3 py-1',
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700',
                            ]"
                            v-html="link.label"
                            @click="router.visit(link.url)"
                        />
                        <span v-else class="min-w-8 rounded border border-gray-200 px-3 py-1 text-gray-400 dark:border-gray-700" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- ── Confirm reset password ──────────────────────────── -->
        <Modal :show="!!showResetConfirm" title="إعادة تعيين كلمة المرور" size="sm" @close="showResetConfirm = null">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                سيتم إنشاء كلمة مرور مؤقتة جديدة للطالب <strong>{{ showResetConfirm?.full_name }}</strong
                >، وسيُطلب منه تغييرها عند أول تسجيل دخول.
            </p>
            <template #footer>
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    @click="showResetConfirm = null"
                >
                    إلغاء
                </button>
                <button type="button" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700" @click="doResetPassword">
                    تأكيد إعادة التعيين
                </button>
            </template>
        </Modal>

        <!-- ── One-time password reveal ────────────────────────── -->
        <Modal :show="showResetResult" title="تم إعادة تعيين كلمة المرور" size="sm" @close="showResetResult = false">
            <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">كلمة المرور المؤقتة الجديدة (انسخها وسلّمها للطالب — لن تظهر مرة أخرى):</p>
            <p class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-center font-mono text-lg font-bold text-amber-800">
                {{ flash.reset_password_value }}
            </p>
            <template #footer>
                <button type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" @click="showResetResult = false">
                    حسناً
                </button>
            </template>
        </Modal>

        <!-- ── Add single student ──────────────────────────────── -->
        <StudentFormModal
            :show="showAddModal"
            :departments="departments"
            :specializations="specializations"
            @close="showAddModal = false"
            @saved="showAddModal = false"
        />
    </AppLayout>
</template>
