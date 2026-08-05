<template>
    <Head title="تعديل مقترح المشروع" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl p-6" dir="rtl">
            <h1 class="mb-6 text-2xl font-bold text-gray-800">تعديل مقترح المشروع</h1>
            <div v-if="proposal" class="rounded bg-white p-6 shadow">
                <ProposalForm
                    :proposal="proposal"
                    :departments="departments"
                    :specializations="specializations"
                    :supervisors="supervisors"
                    @saved="onSaved"
                    @cancel="onCancel"
                />
            </div>
            <div v-else class="py-10 text-center text-gray-500">جاري تحميل بيانات المقترح...</div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import ProposalForm from '../../components/Proposals/ProposalForm.vue';

const props = defineProps({
    proposalId: {
        type: [Number, String],
        required: true,
    },
    specializations: { type: Array, default: () => [] },
    supervisors: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'المقترحات', href: '/proposals' },
    { title: 'تعديل مقترح', href: `/proposals/${props.proposalId}/edit` },
];

const proposal = ref(null);

onMounted(async () => {
    try {
        const response = await axios.get(route('api.proposals.show', props.proposalId));
        proposal.value = response.data.data;
    } catch (error) {
        console.error('Error fetching proposal:', error);
        alert('حدث خطأ أثناء تحميل بيانات المقترح');
        router.visit(route('proposals.index'));
    }
});

const onSaved = () => {
    router.visit(route('proposals.index'), {
        onSuccess: () => alert('تم تعديل المقترح بنجاح'),
    });
};

const onCancel = () => {
    router.visit(route('proposals.index'));
};
</script>
