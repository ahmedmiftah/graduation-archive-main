<template>
  <div class="p-6 max-w-5xl mx-auto">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">تفاصيل المقترح</h1>
      <Link :href="route('proposals.index')" class="text-gray-600 hover:underline">العودة للقائمة</Link>
    </div>

    <div v-if="proposal" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Details -->
      <div class="md:col-span-2 space-y-6">
        <div class="bg-white p-6 rounded shadow">
          <h2 class="text-xl font-semibold mb-4 border-b pb-2">معلومات المشروع</h2>
          <div class="space-y-3 text-sm">
            <p><strong>العنوان:</strong> {{ proposal.title }}</p>
            <p><strong>الوصف:</strong></p>
            <p class="whitespace-pre-wrap text-gray-700 bg-gray-50 p-3 rounded">{{ proposal.description }}</p>
            <p><strong>القسم:</strong> {{ proposal.department?.name || '-' }}</p>
            <p><strong>التخصص:</strong> {{ proposal.specialization?.name || '-' }}</p>
            <p><strong>الفصل الدراسي:</strong> {{ proposal.semester }}</p>
            <p><strong>السنة الأكاديمية:</strong> {{ proposal.academic_year }}</p>
            <p><strong>تاريخ التسليم:</strong> {{ proposal.submission_date || 'غير محدد' }}</p>
          </div>
        </div>

        <div class="bg-white p-6 rounded shadow">
          <h2 class="text-xl font-semibold mb-4 border-b pb-2">ملف المقترح</h2>
          <div v-if="proposal.pdf_url">
            <a :href="proposal.pdf_url" target="_blank" class="text-blue-600 hover:underline flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
              تنزيل / معاينة الملف
            </a>
          </div>
          <p v-else class="text-gray-500">لا يوجد ملف مرفق</p>
        </div>
      </div>

      <!-- Status & Management -->
      <div class="space-y-6">
        <div class="bg-white p-6 rounded shadow">
          <h2 class="text-xl font-semibold mb-4 border-b pb-2">إدارة الحالة</h2>
          <div class="space-y-4">
            <p><strong>المنشئ:</strong> {{ proposal.created_by?.name || '-' }}</p>
            <p><strong>المشرف المقترح:</strong> {{ proposal.supervisor?.name || 'غير محدد' }}</p>
            <p><strong>الحالة الحالية:</strong> <span class="font-bold text-blue-600">{{ proposal.status }}</span></p>
            
            <hr class="my-4" />
            
            <form @submit.prevent="updateStatus" class="space-y-3">
              <div>
                <label class="block text-sm font-medium mb-1">تغيير الحالة</label>
                <select v-model="statusForm.status" class="w-full border rounded p-2">
                  <option value="new">جديد</option>
                  <option value="under_review">قيد المراجعة</option>
                  <option value="approved">تمت الموافقة</option>
                  <option value="rejected">مرفوض</option>
                  <option value="archived">مؤرشف</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium mb-1">قرار اللجنة</label>
                <select v-model="statusForm.committee_decision" class="w-full border rounded p-2">
                  <option :value="null">لم يُحدد</option>
                  <option value="accepted">مقبول</option>
                  <option value="accepted_with_modifications">مقبول مع تعديلات</option>
                  <option value="rejected">مرفوض</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">ملاحظات اللجنة</label>
                <textarea v-model="statusForm.committee_notes" rows="3" class="w-full border rounded p-2"></textarea>
              </div>

              <button type="submit" :disabled="isUpdating" class="w-full bg-green-600 text-white rounded py-2 hover:bg-green-700 disabled:opacity-50">
                {{ isUpdating ? 'جاري التحديث...' : 'حفظ القرار' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="text-center text-gray-500 py-10">
      جاري تحميل البيانات...
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  proposalId: {
    type: [Number, String],
    required: true
  }
});

const proposal = ref(null);
const statusForm = ref({
  status: '',
  committee_decision: null,
  committee_notes: ''
});
const isUpdating = ref(false);

const fetchProposal = async () => {
  try {
    const response = await axios.get(route('api.proposals.show', props.proposalId));
    proposal.value = response.data.data;
    statusForm.value = {
      status: proposal.value.status,
      committee_decision: proposal.value.committee_decision,
      committee_notes: proposal.value.committee_notes || ''
    };
  } catch (error) {
    console.error('Error fetching proposal:', error);
  }
};

onMounted(fetchProposal);

const updateStatus = async () => {
  isUpdating.value = true;
  try {
    await axios.post(route('api.proposals.change-status', props.proposalId), statusForm.value);
    alert('تم تحديث الحالة وقرار اللجنة بنجاح');
    fetchProposal();
  } catch (error) {
    console.error('Error updating status:', error);
    alert('حدث خطأ أثناء التحديث أو لا تملك صلاحية كافية.');
  } finally {
    isUpdating.value = false;
  }
};
</script>
