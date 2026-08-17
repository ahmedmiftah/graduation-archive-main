<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Specialization {
    id: number;
    name: string;
}
interface FacultyMember {
    id: number;
    full_name: string;
    email: string;
}
interface Idea {
    id: number;
    title: string;
    specialization: Specialization | null;
    facultyMember: FacultyMember | null;
}
interface Reservation {
    id: number;
    status: 'reserved' | 'under_review' | 'approved' | 'abandoned' | 'released' | 'finished';
    idea: Idea;
}

const props = defineProps<{
    reservation: Reservation | null;
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/student/dashboard' },
    { title: 'حجزي', href: '/student/reservation' },
];

const STATUS_LABELS: Record<string, string> = {
    reserved: 'محجوز',
    under_review: 'قيد المراجعة',
    approved: 'معتمد',
    abandoned: 'متروك',
    released: 'مُحرَّر',
    finished: 'منتهٍ',
};
const STATUS_COLORS: Record<string, string> = {
    reserved: 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
    under_review: 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400',
    approved: 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
    abandoned: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    released: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    finished: 'bg-purple-100 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400',
};

function markUnderReview() {
    if (!props.reservation) return;
    router.patch(route('student.reservations.under-review', props.reservation.id), {}, { preserveScroll: true });
}

function abandon() {
    if (!props.reservation) return;
    if (!confirm('هل أنت متأكد من التخلي عن هذا الحجز؟')) return;
    router.patch(route('student.reservations.abandon', props.reservation.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="حجزي" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4" dir="rtl">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">حجزي</h1>

            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>

            <div v-if="!props.reservation" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">لا يوجد لديك حجز حاليًا.</p>
                <Link :href="route('student.ideas.index')" class="mt-3 inline-block text-sm font-medium text-blue-600 hover:underline">
                    تصفّح أفكار المشاريع
                </Link>
            </div>

            <div v-else class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">الفكرة المحجوزة</p>
                        <Link :href="route('student.ideas.show', props.reservation.idea.id)" class="mt-1 block font-semibold text-gray-900 hover:underline dark:text-gray-100">
                            {{ props.reservation.idea.title }}
                        </Link>
                    </div>
                    <span :class="['shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium', STATUS_COLORS[props.reservation.status]]">
                        {{ STATUS_LABELS[props.reservation.status] }}
                    </span>
                </div>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                    المشرف: <span class="font-medium text-gray-800 dark:text-gray-200">{{ props.reservation.idea.facultyMember?.full_name ?? '—' }}</span>
                </p>

                <div class="mt-5 flex gap-2">
                    <button
                        v-if="props.reservation.status === 'reserved'"
                        type="button"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        @click="markUnderReview"
                    >
                        بدء العمل على المقترح (قيد المراجعة)
                    </button>
                    <button
                        v-if="['reserved', 'under_review'].includes(props.reservation.status)"
                        type="button"
                        class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-900/20"
                        @click="abandon"
                    >
                        التخلي عن الحجز
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
