<template>
  <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
    <strong class="font-bold">خطأ: </strong>
    <span class="block sm:inline">{{ errorMessage }}</span>
  </div>
  <form @submit.prevent="submitForm" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Title -->
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">عنوان المشروع *</label>
        <input v-model="form.title" type="text" required class="w-full border rounded p-2 focus:ring focus:border-blue-300" />
      </div>

      <!-- Semester -->
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">الفصل الدراسي *</label>
        <select v-model="form.semester" required class="w-full border rounded p-2 focus:ring focus:border-blue-300">
          <option value="" disabled>اختر الفصل</option>
          <option value="ربيع">ربيع</option>
          <option value="خريف">خريف</option>
        </select>
      </div>

      <!-- Academic Year -->
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">السنة الأكاديمية *</label>
        <input v-model="form.academic_year" type="text" placeholder="مثال: 2024" required class="w-full border rounded p-2 focus:ring focus:border-blue-300" />
      </div>

      <!-- Department -->
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">القسم *</label>
        <select 
          v-model="form.department_id" 
          required 
          class="w-full border rounded p-2 focus:ring focus:border-blue-300 disabled:bg-gray-100 disabled:cursor-not-allowed"
          :disabled="!!authUser?.department_id && !isSuperAdmin"
        >
          <option value="" disabled>اختر القسم</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">
            {{ dept.name }}
          </option>
        </select>
      </div>

      <!-- Specialization -->
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">التخصص *</label>
        <select 
          v-model="form.specialization_id" 
          required 
          class="w-full border rounded p-2 focus:ring focus:border-blue-300 disabled:bg-gray-100 disabled:cursor-not-allowed"
          :disabled="!form.department_id"
        >
          <option value="" disabled>{{ form.department_id ? 'اختر التخصص' : 'اختر القسم أولاً' }}</option>
          <option v-for="spec in filteredSpecializations" :key="spec.id" :value="spec.id">
            {{ spec.name }}
          </option>
        </select>
      </div>

      <!-- Supervisor -->
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">المشرف (اختياري)</label>
        <select 
          v-model="form.supervisor_id" 
          class="w-full border rounded p-2 focus:ring focus:border-blue-300 disabled:bg-gray-100 disabled:cursor-not-allowed"
          :disabled="!form.department_id"
        >
          <option value="">بدون مشرف مبدئي</option>
          <option v-for="sup in filteredSupervisors" :key="sup.id" :value="sup.id">
            {{ sup.name }}
          </option>
        </select>
      </div>

      <!-- Submission Date -->
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">تاريخ تسليم المقترح</label>
        <input v-model="form.submission_date" type="date" class="w-full border rounded p-2 focus:ring focus:border-blue-300" />
      </div>

      <!-- Status -->
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">حالة المقترح *</label>
        <select v-model="form.status" required class="w-full border rounded p-2 focus:ring focus:border-blue-300">
          <option value="new">جديد</option>
          <option value="under_review">قيد المراجعة</option>
          <option value="approved">تمت الموافقة</option>
          <option value="rejected">مرفوض</option>
          <option value="archived">مؤرشف</option>
        </select>
      </div>

      <!-- Students Group Box -->
      <div v-if="showStudents" class="border rounded p-4 mb-4">
        <div class="flex justify-between items-center mb-2">
          <h3 class="text-lg font-semibold">الطلاب</h3>
          <button type="button" @click="openStudentModal" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">إضافة طالب</button>
        </div>
        <ul v-if="studentsList.length" class="space-y-2">
          <li v-for="(student, idx) in studentsList" :key="idx" class="flex justify-between items-center bg-gray-100 p-2 rounded">
            <span>{{ student.name }} - {{ student.registration_number }}</span>
            <button type="button" @click="removeStudent(idx)" class="text-red-600 hover:underline">حذف</button>
          </li>
        </ul>
        <p v-else class="text-gray-500">لم يتم إضافة طلاب بعد</p>
      </div>

      <!-- Student Modal -->
      <div v-if="showStudentModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-6 rounded shadow w-80">
          <h4 class="text-lg font-medium mb-4">إضافة طالب</h4>
          <div class="mb-3">
            <label class="block text-sm font-medium mb-1">اسم الطالب</label>
            <input v-model="newStudent.name" type="text" class="w-full border rounded p-2" placeholder="مثال: محمد علي" />
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium mb-1">رقم القيد</label>
            <input v-model="newStudent.registration_number" type="text" class="w-full border rounded p-2" placeholder="مثال: 2021001" />
          </div>
          <div class="flex justify-end space-x-2">
            <button type="button" @click="closeStudentModal" class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100">إلغاء</button>
            <button type="button" @click="addStudent" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">حفظ</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div>
      <label class="block mb-1 text-sm font-medium text-gray-700">وصف المشروع *</label>
      <textarea v-model="form.description" rows="4" required class="w-full border rounded p-2 focus:ring focus:border-blue-300"></textarea>
    </div>

    <!-- File Upload -->
    <div>
      <label class="block mb-1 text-sm font-medium text-gray-700">ملف PDF للمقترح</label>
      <input type="file" accept="application/pdf" @change="handleFileUpload" class="w-full border rounded p-2 focus:ring focus:border-blue-300" />
      <p class="text-sm text-gray-500 mt-1">الحد الأقصى 5MB (PDF فقط)</p>
    </div>

    <div class="flex justify-end gap-2">
      <button type="button" @click="$emit('cancel')" class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50">إلغاء</button>
      <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
        {{ isSubmitting ? 'جاري الحفظ...' : 'حفظ المقترح' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  proposal: {
    type: Object,
    default: null
  },
  departments: {
    type: Array,
    default: () => []
  },
  specializations: {
    type: Array,
    default: () => []
  },
  supervisors: {
    type: Array,
    default: () => []
  },
  showStudents: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['saved', 'cancel']);

const form = ref({
  title: '',
  description: '',
  department_id: '',
  specialization_id: '',
  semester: '',
  academic_year: '',
  submission_date: '',
  supervisor_id: '',
  status: 'new',
  pdf_file: null,
});

import { usePage } from '@inertiajs/vue3';
const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const isSuperAdmin = computed(() => {
  return authUser.value?.role === 'super_admin';
});

const filteredSpecializations = computed(() => {
  if (!form.value.department_id) return [];
  return props.specializations.filter(s => s.department_id === form.value.department_id);
});

const filteredSupervisors = computed(() => {
  if (!form.value.department_id) return [];
  return props.supervisors.filter(s => s.department_id === form.value.department_id);
});

watch(() => form.value.department_id, (newVal, oldVal) => {
  if (oldVal) {
    form.value.specialization_id = '';
    form.value.supervisor_id = '';
  }
});

const studentsList = ref([]);
const showStudentModal = ref(false);
const newStudent = ref({ name: '', registration_number: '' });

const isSubmitting = ref(false);
const errorMessage = ref('');

onMounted(() => {
  if (props.proposal) {
    form.value = {
      title: props.proposal.title || '',
      description: props.proposal.description || '',
      department_id: props.proposal.department_id || (props.proposal.specialization?.department_id) || '',
      specialization_id: props.proposal.specialization?.id || '',
      semester: props.proposal.semester || '',
      academic_year: props.proposal.academic_year || '',
      submission_date: props.proposal.submission_date 
        ? props.proposal.submission_date.split('T')[0] 
        : '',
      supervisor_id: props.proposal.supervisor?.id || '',
      status: props.proposal.status || 'new',
      pdf_file: null
    };
    if (props.proposal.students && props.proposal.students.length) {
      studentsList.value = props.proposal.students.map(s => ({
        name: s.name || '',
        registration_number: s.registration_number || ''
      }));
    }
  } else {
    // New proposal: default department_id if not super_admin
    if (authUser.value?.department_id) {
      form.value.department_id = authUser.value.department_id;
    }
  }
});

const handleFileUpload = (e) => {
  const file = e.target.files[0];
  if (file && file.type === 'application/pdf' && file.size <= 5 * 1024 * 1024) {
    form.value.pdf_file = file;
  } else {
    alert('يرجى اختيار ملف PDF حجمه لا يتجاوز 5MB');
    e.target.value = '';
    form.value.pdf_file = null;
  }
};

const openStudentModal = () => {
  newStudent.value = { name: '', registration_number: '' };
  showStudentModal.value = true;
};
const closeStudentModal = () => {
  showStudentModal.value = false;
};
const addStudent = () => {
  if (!newStudent.value.name || !newStudent.value.registration_number) {
    alert('يرجى ملء اسم الطالب ورقم القيد');
    return;
  }
  studentsList.value.push({ ...newStudent.value });
  closeStudentModal();
};
const removeStudent = (index) => {
  studentsList.value.splice(index, 1);
};

const submitForm = async () => {
  isSubmitting.value = true;
  errorMessage.value = '';
  const studentRegs = props.showStudents ? studentsList.value.map(s => s.registration_number.trim()).filter(r => r) : [];
  const formData = new FormData();
  Object.keys(form.value).forEach(key => {
    if (form.value[key] !== null && form.value[key] !== '') {
      formData.append(key, form.value[key]);
    }
  });
  if (props.showStudents) {
    studentRegs.forEach(reg => formData.append('students[]', reg));
  }

  try {
    if (props.proposal && props.proposal.id) {
      formData.append('_method', 'PUT');
      await axios.post(route('api.proposals.update', props.proposal.id), formData);
    } else {
      await axios.post(route('api.proposals.store'), formData);
    }
    emit('saved');
  } catch (error) {
    console.error('Submission Error:', error);
    if (error.response && error.response.data && error.response.data.message) {
      errorMessage.value = error.response.data.message;
    } else {
      errorMessage.value = 'حدث خطأ أثناء الحفظ. يرجى التحقق من المدخلات.';
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>
