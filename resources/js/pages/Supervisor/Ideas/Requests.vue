<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Student {
    id: number;
    full_name: string;
    registration_number: string;
}
interface Reservation {
    id: number;
    status: 'reserved' | 'under_review' | 'approved' | 'abandoned' | 'released' | 'finished';
}
interface IdeaRequest {
    id: number;
    status: 'pending' | 'accepted' | 'rejected';
    message: string | null;
    created_at: string;
    student: Student;
    reservation: Reservation | null;
}
interface Idea {
    id: number;
    title: string;
    required_students_count: number;
    status: string;
}

const props = defineProps<{
    idea: Idea;
    requests: IdeaRequest[];
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/supervisor/dashboard' },
    { title: 'أفكار المشاريع', href: '/supervisor/ideas' },
    { title: props.idea.title, href: '' },
];

const STATUS_LABELS: Record<string, string> = { pending: 'قيد المراجعة', accepted: 'مقبول', rejected: 'مرفوض' };
const STATUS_COLORS: Record<string, string> = {
    pending: 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
    accepted: 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400',
};

const RESERVATION_LABELS: Record<string, string> = {
    reserved: 'الحجز: محجوز',
    under_review: 'الحجز: قيد المراجعة',
    approved: 'الحجز: معتمد',
    abandoned: 'الحجز: متروك',
    released: 'الحجز: مُحرَّر',
    finished: 'الحجز: منتهٍ',
};
const RESERVATION_COLORS: Record<string, string> = {
    reserved: 'bg-blue-50 text-blue-600 dark:bg-blue-900/10 dark:text-blue-400',
    under_review: 'bg-orange-50 text-orange-600 dark:bg-orange-900/10 dark:text-orange-400',
    approved: 'bg-green-50 text-green-600 dark:bg-green-900/10 dark:text-green-400',
    abandoned: 'bg-gray-50 text-gray-500 dark:bg-gray-800/50 dark:text-gray-400',
    released: 'bg-gray-50 text-gray-500 dark:bg-gray-800/50 dark:text-gray-400',
    finished: 'bg-purple-50 text-purple-600 dark:bg-purple-900/10 dark:text-purple-400',
};

function accept(requestId: number) {
    router.patch(route('supervisor.idea-requests.accept', requestId), {}, { preserveScroll: true });
}

function reject(requestId: number) {
    router.patch(route('supervisor.idea-requests.reject', requestId), {}, { preserveScroll: true });
}

function approveReservation(reservationId: number) {
    router.patch(route('supervisor.reservations.approve', reservationId), {}, { preserveScroll: true });
}

function releaseReservation(reservationId: number) {
    router.patch(route('supervisor.reservations.release', reservationId), {}, { preserveScroll: true });
}

function finishReservation(reservationId: number) {
    router.patch(route('supervisor.reservations.finish', reservationId), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="'طلبات فكرة: ' + props.idea.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <div>
                <Link :href="route('supervisor.ideas.index')" class="text-sm text-blue-600 hover:underline">← العودة لأفكاري</Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">طلبات الانضمام: {{ props.idea.title }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">العدد المطلوب: {{ props.idea.required_students_count }}</p>
            </div>

            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>

            <div v-if="props.requests.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">لا توجد طلبات انضمام لهذه الفكرة بعد.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="r in props.requests"
                    :key="r.id"
                    class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ r.student.full_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ r.student.registration_number }} · {{ r.created_at }}</p>
                        </div>
                        <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', STATUS_COLORS[r.status]]">{{ STATUS_LABELS[r.status] }}</span>
                    </div>
                    <p v-if="r.message" class="mt-3 text-sm text-gray-600 dark:text-gray-400">{{ r.message }}</p>

                    <div v-if="r.status === 'pending'" class="mt-4 flex gap-2">
                        <button
                            type="button"
                            class="rounded-lg bg-green-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                            @click="accept(r.id)"
                        >
                            قبول
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-red-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                            @click="reject(r.id)"
                        >
                            رفض
                        </button>
                    </div>

                    <div v-if="r.reservation" class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">
                        <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', RESERVATION_COLORS[r.reservation.status]]">
                            {{ RESERVATION_LABELS[r.reservation.status] }}
                        </span>
                        <button
                            v-if="r.reservation.status === 'under_review'"
                            type="button"
                            class="rounded-lg bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700"
                            @click="approveReservation(r.reservation.id)"
                        >
                            اعتماد الحجز
                        </button>
                        <button
                            v-if="r.reservation.status === 'abandoned'"
                            type="button"
                            class="rounded-lg bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700"
                            @click="releaseReservation(r.reservation.id)"
                        >
                            تحرير الحجز
                        </button>
                        <button
                            v-if="r.reservation.status === 'approved'"
                            type="button"
                            class="rounded-lg bg-purple-600 px-3 py-1 text-xs font-medium text-white hover:bg-purple-700"
                            @click="finishReservation(r.reservation.id)"
                        >
                            إنهاء الحجز
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
