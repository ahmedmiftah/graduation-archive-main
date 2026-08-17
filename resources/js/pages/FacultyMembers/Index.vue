<script setup lang="ts">
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import Modal from '@/components/Modal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type AcademicDegree, type BreadcrumbItem, type Department, type FacultyMember, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

const props = defineProps<{
    facultyMembers: (FacultyMember & { departments: Department[]; projects_count: number })[];
    departments: Department[];
    degrees: AcademicDegree[];
    filters: { department_id?: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'أعضاء هيئة التدريس', href: '/faculty-members' },
];

// ── Add / Edit modal ──────────────────────────────────────────────
const showModal = ref(false);
const editingMember = ref<FacultyMember | null>(null);

const form = useForm({
    full_name: '',
    phone_number: '',
    email: '',
    degree_id: 0 as number,
    department_ids: [] as number[],
});

function openAdd() {
    editingMember.value = null;
    form.reset();
    showModal.value = true;
}

function openEdit(member: FacultyMember & { departments: Department[] }) {
    editingMember.value = member;
    form.full_name = member.full_name;
    form.phone_number = member.phone_number;
    form.email = member.email;
    form.degree_id = member.degree_id;
    form.department_ids = member.departments.map((d) => d.id);
    showModal.value = true;
}

// ── Create login account ─────────────────────────────────────────
const confirmCreateAccount = ref<FacultyMember | null>(null);

function createAccount() {
    if (!confirmCreateAccount.value) return;
    router.post(
        route('faculty-members.create-account', [confirmCreateAccount.value.id]),
        {},
        { onFinish: () => (confirmCreateAccount.value = null) },
    );
}

function toggleDepartment(id: number) {
    const idx = form.department_ids.indexOf(id);
    if (idx === -1) {
        form.department_ids.push(id);
    } else {
        form.department_ids.splice(idx, 1);
    }
}

function submitForm() {
    const options = {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    };
    if (editingMember.value) {
        form.put(route('faculty-members.update', [editingMember.value.id]), options);
    } else {
        form.post(route('faculty-members.store'), options);
    }
}

// ── Delete ────────────────────────────────────────────────────────
const confirmDelete = ref<FacultyMember | null>(null);

function deleteMember() {
    if (!confirmDelete.value) return;
    router.delete(route('faculty-members.destroy', [confirmDelete.value.id]), {
        onFinish: () => (confirmDelete.value = null),
    });
}
</script>

<template>
    <Head title="أعضاء هيئة التدريس" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">إدارة أعضاء هيئة التدريس</h1>
                <button type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" @click="openAdd">
                    + إضافة عضو هيئة تدريس
                </button>
            </div>

            <!-- Flash messages -->
            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                {{ flash.error }}
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الاسم الكامل</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الدرجة العلمية</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">رقم الهاتف</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">البريد الإلكتروني</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الأقسام</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">المشاريع</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">حساب الدخول</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr v-for="member in props.facultyMembers" :key="member.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ member.full_name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ member.degree?.degree_code ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ member.phone_number }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ member.email }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ member.departments.map((d) => d.name).join('، ') || '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                <span
                                    class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400"
                                >
                                    {{ member.projects_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    v-if="member.user_id"
                                    class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400"
                                >
                                    لديه حساب
                                </span>
                                <span v-else class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    بدون حساب
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400"
                                        @click="openEdit(member)"
                                    >
                                        تعديل
                                    </button>
                                    <button
                                        v-if="!member.user_id"
                                        type="button"
                                        class="rounded bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-200 dark:bg-blue-900/20 dark:text-blue-400"
                                        @click="confirmCreateAccount = member"
                                    >
                                        إنشاء حساب دخول
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded bg-red-100 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400"
                                        @click="confirmDelete = member"
                                    >
                                        حذف
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="props.facultyMembers.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">لا يوجد أعضاء هيئة تدريس مسجلون</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Modal :show="showModal" :title="editingMember ? 'تعديل بيانات عضو هيئة التدريس' : 'إضافة عضو هيئة تدريس جديد'" @close="showModal = false">
            <form id="faculty-member-form" class="space-y-4" @submit.prevent="submitForm">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الاسم الكامل</label>
                    <input
                        v-model="form.full_name"
                        type="text"
                        maxlength="150"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.full_name }"
                    />
                    <p v-if="form.errors.full_name" class="mt-1 text-xs text-red-600">{{ form.errors.full_name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">رقم الهاتف</label>
                    <input
                        v-model="form.phone_number"
                        type="text"
                        maxlength="30"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.phone_number }"
                    />
                    <p v-if="form.errors.phone_number" class="mt-1 text-xs text-red-600">{{ form.errors.phone_number }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">البريد الإلكتروني</label>
                    <input
                        v-model="form.email"
                        type="email"
                        maxlength="150"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الدرجة العلمية</label>
                    <select
                        v-model="form.degree_id"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.degree_id }"
                    >
                        <option :value="0" disabled>-- اختر الدرجة العلمية --</option>
                        <option v-for="degree in props.degrees" :key="degree.id" :value="degree.id">
                            {{ degree.degree_code }}
                        </option>
                    </select>
                    <p v-if="form.errors.degree_id" class="mt-1 text-xs text-red-600">{{ form.errors.degree_id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الأقسام</label>
                    <div class="mt-1 grid grid-cols-2 gap-2 rounded-lg border border-gray-300 p-3 dark:border-gray-600">
                        <label v-for="dept in props.departments" :key="dept.id" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                :checked="form.department_ids.includes(dept.id)"
                                class="rounded border-gray-300"
                                @change="toggleDepartment(dept.id)"
                            />
                            {{ dept.name }}
                        </label>
                    </div>
                    <p v-if="form.errors.department_ids" class="mt-1 text-xs text-red-600">{{ form.errors.department_ids }}</p>
                </div>
            </form>

            <template #footer>
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    @click="showModal = false"
                >
                    إلغاء
                </button>
                <button
                    type="submit"
                    form="faculty-member-form"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ editingMember ? 'تحديث' : 'حفظ' }}
                </button>
            </template>
        </Modal>

        <!-- Confirm delete -->
        <ConfirmDelete :show="!!confirmDelete" :item-name="confirmDelete?.full_name" @confirmed="deleteMember" @cancelled="confirmDelete = null" />

        <!-- Confirm create account -->
        <Modal :show="!!confirmCreateAccount" title="إنشاء حساب دخول" size="sm" @close="confirmCreateAccount = null">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                سيتم إنشاء حساب دخول لعضو هيئة التدريس <strong>{{ confirmCreateAccount?.full_name }}</strong
                >. اسم الدخول سيكون بريده الإلكتروني المسجَّل ({{ confirmCreateAccount?.email }})، وكلمة المرور المؤقتة هي رقم جواله المسجَّل — سيُطلب
                منه تغييرها عند أول دخول.
            </p>
            <template #footer>
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    @click="confirmCreateAccount = null"
                >
                    إلغاء
                </button>
                <button type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" @click="createAccount">
                    تأكيد الإنشاء
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
