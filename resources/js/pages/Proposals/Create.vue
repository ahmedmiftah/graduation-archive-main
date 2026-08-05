<template>
    <Head title="إنشاء مقترح جديد" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl p-6" dir="rtl">
            <h1 class="mb-6 text-2xl font-bold text-gray-800">إنشاء مقترح جديد</h1>
            <div class="rounded bg-white p-6 shadow">
                <ProposalForm
                    :departments="departments"
                    :specializations="specializations"
                    :supervisors="supervisors"
                    @saved="onSaved"
                    @cancel="onCancel"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import ProposalForm from '../../components/Proposals/ProposalForm.vue';

const breadcrumbs = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'المقترحات', href: '/proposals' },
    { title: 'إنشاء مقترح جديد', href: '/proposals/create' },
];

const props = defineProps({
    departments: { type: Array, default: () => [] },
    specializations: { type: Array, default: () => [] },
    supervisors: { type: Array, default: () => [] },
});

const onSaved = () => {
    router.visit(route('proposals.index'), {
        onSuccess: () => alert('تم إنشاء المقترح بنجاح'),
    });
};

const onCancel = () => {
    router.visit(route('proposals.index'));
};
</script>
