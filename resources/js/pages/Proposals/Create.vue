<template>
  <div class="p-6 max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">إنشاء مقترح جديد</h1>
    <Link :href="route('proposals.index')" class="text-gray-600 hover:underline mb-4 inline-block">العودة للقائمة</Link>
    <div class="bg-white p-6 rounded shadow">
      <ProposalForm :specializations="specializations" :supervisors="supervisors" @saved="onSaved" @cancel="onCancel" />
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import ProposalForm from '../../components/Proposals/ProposalForm.vue';

const props = defineProps({
  specializations: { type: Array, default: () => [] },
  supervisors: { type: Array, default: () => [] }
});

const onSaved = () => {
  router.visit(route('proposals.index'), {
    onSuccess: () => alert('تم إنشاء المقترح بنجاح')
  });
};

const onCancel = () => {
  router.visit(route('proposals.index'));
};
</script>
