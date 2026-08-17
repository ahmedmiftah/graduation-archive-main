<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Department {
    id: number;
    name: string;
}
interface Specialization {
    id: number;
    name: string;
    department: Department | null;
}
interface FacultyMember {
    id: number;
    full_name: string;
    email: string;
}
interface Idea {
    id: number;
    title: string;
    description: string;
    specialization: Specialization | null;
    facultyMember: FacultyMember | null;
    required_students_count: number;
    skills: string | null;
    keywords: string | null;
    notes: string | null;
}
interface MyRequest {
    id: number;
    status: 'pending' | 'accepted' | 'rejected';
    message: string | null;
}

const props = defineProps<{
    idea: Idea;
    myRequest: MyRequest | null;
    canApply: boolean;
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/student/dashboard' },
    { title: 'أفكار المشاريع', href: '/student/ideas' },
    { title: props.idea.title, href: '' },
];

const REQUEST_STATUS_LABELS: Record<string, string> = {
    pending: 'طلبك قيد المراجعة',
    accepted: 'تم قبول طلبك',
    rejected: 'تم رفض طلبك',
};

const form = useForm({ message: '' });

function submitRequest() {
    form.post(route('student.ideas.requests.store', props.idea.id), {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head :title="props.idea.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4" dir="rtl">
            <Link :href="route('student.ideas.index')" class="text-sm text-blue-600 hover:underline">← العودة لتصفّح الأفكار</Link>

            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ props.idea.title }}</h1>
                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ props.idea.description }}</p>

                <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-gray-100 pt-4 dark:border-gray-700 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">القسم</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ props.idea.specialization?.department?.name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">التخصص</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ props.idea.specialization?.name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">عدد الطلاب المطلوب</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ props.idea.required_students_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">المشرف المقترح</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-200">{{ props.idea.facultyMember?.full_name ?? '—' }}</dd>
                    </div>
                </dl>

                <div v-if="props.idea.skills" class="mt-4">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">المهارات المطلوبة</dt>
                    <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">{{ props.idea.skills }}</dd>
                </div>
                <div v-if="props.idea.keywords" class="mt-4">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">كلمات مفتاحية</dt>
                    <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">{{ props.idea.keywords }}</dd>
                </div>
                <div v-if="props.idea.notes" class="mt-4">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">ملاحظات المشرف</dt>
                    <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">{{ props.idea.notes }}</dd>
                </div>
            </div>

            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>

            <!-- Already applied -->
            <div v-if="props.myRequest" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <p
                    class="text-sm font-medium"
                    :class="{
                        'text-blue-600 dark:text-blue-400': props.myRequest.status === 'pending',
                        'text-green-600 dark:text-green-400': props.myRequest.status === 'accepted',
                        'text-red-600 dark:text-red-400': props.myRequest.status === 'rejected',
                    }"
                >
                    {{ REQUEST_STATUS_LABELS[props.myRequest.status] }}
                </p>
                <p v-if="props.myRequest.message" class="mt-2 text-sm text-gray-600 dark:text-gray-400">رسالتك: {{ props.myRequest.message }}</p>
            </div>

            <!-- Request-to-join form -->
            <div v-else-if="props.canApply" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-200">طلب الانضمام لهذه الفكرة</h2>
                <form class="space-y-3" @submit.prevent="submitRequest">
                    <div>
                        <textarea
                            v-model="form.message"
                            rows="3"
                            placeholder="رسالة مختصرة للمشرف (اختياري)"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p v-if="form.errors.message" class="mt-1 text-xs text-red-600">{{ form.errors.message }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        إرسال طلب الانضمام
                    </button>
                </form>
            </div>

            <div v-else class="rounded-xl border border-dashed border-gray-300 bg-white p-5 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                لا يمكنك حاليًا طلب الانضمام لهذه الفكرة — إمّا أنها لم تعد متاحة، أو لديك بالفعل مشروع نشط أو طلب مقبول في مكان آخر.
            </div>
        </div>
    </AppLayout>
</template>
