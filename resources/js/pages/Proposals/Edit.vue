<template>
  <div class="p-6 max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">تعديل مقترح المشروع</h1>
    <div v-if="proposal" class="bg-white p-6 rounded shadow">
      <ProposalForm :proposal="proposal" :departments="departments" :specializations="specializations" :supervisors="supervisors" @saved="onSaved" @cancel="onCancel" />
    </div>
    <div v-else class="text-center text-gray-500 py-10">
      جاري تحميل بيانات المقترح...
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import ProposalForm from '../../components/Proposals/ProposalForm.vue';

const props = defineProps({
  proposalId: {
    type: [Number, String],
    required: true
  },
  specializations: { type: Array, default: () => [] },
  supervisors: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
});

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
    onSuccess: () => alert('تم تعديل المقترح بنجاح')
  });
};

const onCancel = () => {
  router.visit(route('proposals.index'));
};
</script>
