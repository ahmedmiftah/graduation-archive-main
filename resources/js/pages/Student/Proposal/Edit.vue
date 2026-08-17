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
interface ProposalStudent {
    id: number;
    full_name: string;
    registration_number: string;
    phone_number: string | null;
}
interface Proposal {
    id: number;
    title: string;
    description: string;
    specialization_id: number;
    semester: string;
    academic_year: string;
    submission_date: string | null;
    supervisor_id: number | null;
    students: ProposalStudent[];
}

const props = defineProps<{
    proposal: Proposal;
    formOptions: { specializations: Specialization[]; supervisors: Supervisor[] };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/student/dashboard' },
    { title: 'تعديل المقترح', href: `/student/proposal/${props.proposal.id}/edit` },
];

function backToProposal() {
    router.visit(route('student.proposal.show', props.proposal.id));
}
</script>

<template>
    <Head title="تعديل المقترح" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4" dir="rtl">
            <ProposalFormModal
                :show="true"
                mode="edit"
                :proposal="proposal"
                :specializations="formOptions.specializations"
                :supervisors="formOptions.supervisors"
                update-route-name="student.proposal.update"
                @close="backToProposal"
            />
        </div>
    </AppLayout>
</template>
