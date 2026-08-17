<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface StudentDetail {
    id: number;
    full_name: string;
    national_id: string;
    registration_number: string;
    semester: string;
    academic_year: string;
    date_of_birth: string;
    department: { id: number; name: string };
    specialization: { id: number; name: string };
    user: { id: number; email: string; is_active: boolean; created_at: string } | null;
}

const props = defineProps<{ student: StudentDetail }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'الطلاب', href: '/students' },
    { title: props.student.full_name, href: `/students/${props.student.id}` },
];
</script>

<template>
    <Head :title="student.full_name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4" dir="rtl">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ student.full_name }}</h1>
                <Link :href="route('students.index')" class="text-sm text-blue-600 hover:underline">→ العودة إلى قائمة الطلاب</Link>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">بيانات الطالب</h2>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">رقم القيد</dt>
                        <dd class="font-mono text-sm font-medium text-gray-900 dark:text-gray-100">{{ student.registration_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">الرقم الوطني</dt>
                        <dd class="font-mono text-sm font-medium text-gray-900 dark:text-gray-100">{{ student.national_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">القسم</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ student.department.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">التخصص</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ student.specialization.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">الفصل الدراسي</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ student.semester }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">السنة الدراسية</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ student.academic_year }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">تاريخ الميلاد</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ student.date_of_birth }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">حساب الدخول</h2>
                <dl v-if="student.user" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">حالة الحساب</dt>
                        <dd>
                            <span
                                :class="
                                    student.user.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'
                                "
                                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ student.user.is_active ? 'نشط' : 'غير نشط' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">تاريخ إنشاء الحساب</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ new Date(student.user.created_at).toLocaleDateString('ar-EG') }}
                        </dd>
                    </div>
                </dl>
                <p v-else class="text-sm text-gray-500">لا يوجد حساب دخول لهذا الطالب</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-2 text-base font-semibold text-gray-800 dark:text-gray-200">مشروع التخرج</h2>
                <p class="text-sm text-gray-400">لم يبدأ المشروع بعد</p>
            </div>
        </div>
    </AppLayout>
</template>
