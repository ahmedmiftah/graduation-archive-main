<script setup lang="ts">
import ProposalFormModal from '@/components/Proposals/ProposalFormModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

interface Specialization {
    id: number;
    name: string;
    department_id: number;
}
interface Supervisor {
    id: number;
    full_name: string;
}

defineProps<{
    formOptions: { specializations: Specialization[]; supervisors: Supervisor[] };
    defaults: { specialization_id: number; academic_year: string; semester: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/student/dashboard' },
    { title: 'تقديم مقترح', href: '/student/proposal/create' },
];

function backToDashboard() {
    router.visit(route('student.dashboard'));
}
</script>

<template>
    <Head title="تقديم مقترح جديد" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4" dir="rtl">
            <ProposalFormModal
                :show="true"
                mode="create"
                :specializations="formOptions.specializations"
                :supervisors="formOptions.supervisors"
                :defaults="defaults"
                store-route-name="student.proposal.store"
                @close="backToDashboard"
            />
        </div>
    </AppLayout>
</template>
