<!-- Statistics page removed -->
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Inertia } from '@inertiajs/vue3';
import ProjectStatusChart from '@/components/ProjectStatusChart.vue';
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';

interface DeptItem {
  id: number;
  name: string;
  project_count: number;
}

const page = usePage<any>();
const role = computed(() => page.props.auth?.user?.role ?? '');

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'لوحة التحكم', href: '/dashboard' },
  { title: 'الإحصائيات', href: '/statistics' },
];

// Data from backend
const departments = computed<DeptItem[]>(() => page.props.stats?.departments ?? []);

// Super admin filter for department
const isSuperAdmin = computed(() => role.value === 'super_admin');
const selectedDept = ref('');
const departmentOptions = computed(() => departments.value.map(d => ({ id: d.id, name: d.name })));
const filteredDepartments = computed(() => {
  if (!isSuperAdmin.value || !selectedDept.value) return departments.value;
  return departments.value.filter(d => d.id === Number(selectedDept.value));
});

// Degree level filter (available for all users)
const degreeOptions = [
  { value: '', label: 'الكل' },
  { value: 'diploma', label: 'دبلوم' },
  { value: 'bachelor', label: 'بكالوريوس' },
  { value: 'master', label: 'ماجستير' },
];
const selectedDegree = ref(page.props.stats?.degree_level ?? '');

// Reload page when degree changes (preserve other query params)
watch(selectedDegree, (newVal) => {
  Inertia.get('/statistics', { degree_level: newVal }, { replace: true, preserveState: true, preserveScroll: true });
});

// Chart data based on filtered departments
const chartData = computed(() =>
  filteredDepartments.value.map(d => ({ status_name: d.name, count: d.project_count }))
);
</script>

<template>
  <Head title="الإحصائيات" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="printable-area flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">إحصائيات المشاريع حسب الأقسام</h1>
      <!-- Super admin department selector -->
      <div v-if="isSuperAdmin" class="mb-4">
        <label class="mr-2 font-medium" for="dept-select">اختر قسمًا:</label>
        <select id="dept-select" v-model="selectedDept" class="border rounded px-2 py-1">
          <option value="">الكل</option>
          <option v-for="opt in departmentOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
        </select>
      </div>
      <!-- Degree level selector -->
      <div class="mb-4">
        <label class="mr-2 font-medium" for="degree-select">اختر فئة:</label>
        <select id="degree-select" v-model="selectedDegree" class="border rounded px-2 py-1">
          <option v-for="opt in degreeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </div>
      <ProjectStatusChart :statusData="chartData" />
    </div>
  </AppLayout>
</template>
