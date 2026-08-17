<script setup lang="ts">
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import Modal from '@/components/Modal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type AcademicDegree, type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

defineProps<{
    degrees: AcademicDegree[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'الدرجات العلمية', href: '/academic-degrees' },
];

// ── Add modal ──────────────────────────────────────────────
const showModal = ref(false);

const form = useForm({
    degree_name: '',
    degree_code: '',
});

function openAdd() {
    form.reset();
    showModal.value = true;
}

function submitForm() {
    form.post(route('academic-degrees.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
}

// ── Delete ────────────────────────────────────────────────────────
const confirmDelete = ref<AcademicDegree | null>(null);

function deleteDegree() {
    if (!confirmDelete.value) return;
    router.delete(route('academic-degrees.destroy', [confirmDelete.value.id]), {
        onFinish: () => (confirmDelete.value = null),
    });
}
</script>

<template>
    <Head title="الدرجات العلمية" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">إدارة الدرجات العلمية</h1>
                <button type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" @click="openAdd">
                    + إضافة درجة علمية
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
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">اسم الدرجة العلمية</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">الرمز العلمي</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr v-for="degree in degrees" :key="degree.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ degree.degree_name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ degree.degree_code }}
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    class="rounded bg-red-100 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400"
                                    @click="confirmDelete = degree"
                                >
                                    حذف
                                </button>
                            </td>
                        </tr>
                        <tr v-if="degrees.length === 0">
                            <td colspan="3" class="px-4 py-10 text-center text-sm text-gray-500">لا توجد درجات علمية مسجلة</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Modal -->
        <Modal :show="showModal" title="إضافة درجة علمية جديدة" @close="showModal = false">
            <form id="academic-degree-form" class="space-y-4" @submit.prevent="submitForm">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">اسم الدرجة العلمية</label>
                    <input
                        v-model="form.degree_name"
                        type="text"
                        maxlength="150"
                        placeholder="مثال: بكالوريوس"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.degree_name }"
                    />
                    <p v-if="form.errors.degree_name" class="mt-1 text-xs text-red-600">{{ form.errors.degree_name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الرمز العلمي</label>
                    <input
                        v-model="form.degree_code"
                        type="text"
                        maxlength="20"
                        placeholder="مثال: B.Sc"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-500': form.errors.degree_code }"
                    />
                    <p v-if="form.errors.degree_code" class="mt-1 text-xs text-red-600">{{ form.errors.degree_code }}</p>
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
                    form="academic-degree-form"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    حفظ
                </button>
            </template>
        </Modal>

        <!-- Confirm delete -->
        <ConfirmDelete :show="!!confirmDelete" :item-name="confirmDelete?.degree_name" @confirmed="deleteDegree" @cancelled="confirmDelete = null" />
    </AppLayout>
</template>
