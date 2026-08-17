<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

// ── Types ────────────────────────────────────────────────────────────────────

interface PreviewRow {
    row_number: number;
    full_name: string;
    national_id: string;
    registration_number: string;
    department_code: string;
    specialization_name: string;
    semester: string;
    academic_year: string;
    valid: boolean;
    status: string;
    error: string | null;
}

interface ImportSummary {
    total_rows: number;
    valid_count: number;
    success_count: number;
    duplicate_count: number;
    already_exists_count: number;
    invalid_national_id_count: number;
    invalid_registration_count: number;
    invalid_data_count: number;
    failed_count: number;
    failed_rows: { row_number: number; reason: string; status: string }[];
    preview_rows?: PreviewRow[];
}

const page = usePage<SharedData & { flash: { student_import_preview?: ImportSummary; student_import_summary?: ImportSummary } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'استيراد الطلاب', href: '/students/import' },
];

// ── State ────────────────────────────────────────────────────────────────────

const currentStep = ref(1);
const selectedFile = ref<File | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const previewResult = ref<ImportSummary | null>(null);
const importResult = ref<ImportSummary | null>(null);

const excelForm = useForm<{ file: File | null }>({ file: null });

// ── Flash watchers ───────────────────────────────────────────────────────────

watch(
    () => page.props.flash?.student_import_preview,
    (val) => {
        if (val) {
            previewResult.value = val;
            currentStep.value = 3;
        }
    },
    { immediate: true },
);

watch(
    () => page.props.flash?.student_import_summary,
    (val) => {
        if (val) {
            importResult.value = val;
            currentStep.value = 4;
        }
    },
    { immediate: true },
);

// ── Actions ──────────────────────────────────────────────────────────────────

function onFileChange(e: Event) {
    selectedFile.value = (e.target as HTMLInputElement).files?.[0] ?? null;
    excelForm.clearErrors();
}

function doPreview() {
    excelForm.file = selectedFile.value;
    excelForm.post(route('students.import.preview'), { preserveState: true, preserveScroll: true });
}

function doImport() {
    excelForm.file = selectedFile.value;
    excelForm.post(route('students.import.run'), { preserveState: true, preserveScroll: true });
}

function goToStep(n: number) {
    currentStep.value = n;
}

function resetWizard() {
    currentStep.value = 1;
    selectedFile.value = null;
    previewResult.value = null;
    importResult.value = null;
    excelForm.reset();
    if (fileInputRef.value) fileInputRef.value.value = '';
}

const statusLabels: Record<string, string> = {
    valid: 'صالح',
    duplicate_in_file: 'مكرر داخل الملف',
    already_exists: 'موجود مسبقاً',
    invalid_national_id: 'رقم وطني غير صحيح',
    invalid_registration_number: 'رقم قيد غير صحيح',
    invalid_data: 'بيانات ناقصة/غير صحيحة',
};

const columns = [
    { name: 'full_name', label: 'اسم الطالب', required: true },
    { name: 'national_id', label: 'الرقم الوطني (12 رقماً)', required: true },
    { name: 'registration_number', label: 'رقم القيد', required: true },
    { name: 'department_code', label: 'رمز القسم', required: true },
    { name: 'specialization_name', label: 'اسم التخصص', required: true },
    { name: 'semester', label: 'الفصل الدراسي', required: true },
    { name: 'academic_year', label: 'السنة الدراسية', required: true },
    { name: 'date_of_birth', label: 'تاريخ الميلاد (YYYY-MM-DD)', required: true },
];

const summaryCards = computed(() => {
    if (!previewResult.value) return [];
    const r = previewResult.value;
    return [
        { label: 'طالب صالح', value: r.valid_count, color: 'green' },
        { label: 'مكرر (داخل الملف/موجود مسبقاً)', value: r.duplicate_count + r.already_exists_count, color: 'amber' },
        { label: 'أرقام وطنية غير صحيحة', value: r.invalid_national_id_count, color: 'red' },
        { label: 'أرقام قيد غير صحيحة', value: r.invalid_registration_count, color: 'red' },
    ];
});
</script>

<template>
    <Head title="استيراد الطلاب" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4" dir="rtl">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">استيراد كشف الطلاب</h1>

            <!-- ── Step indicator ─────────────────────────────────────────── -->
            <div class="flex items-center gap-0">
                <template v-for="(label, idx) in ['تحميل القالب', 'رفع ومعاينة', 'تأكيد الاستيراد']" :key="idx">
                    <div class="flex flex-col items-center">
                        <button
                            class="flex h-9 w-9 items-center justify-center rounded-full border-2 text-sm font-bold transition-all"
                            :class="[
                                currentStep === idx + 1
                                    ? 'border-blue-600 bg-blue-600 text-white'
                                    : currentStep > idx + 1
                                      ? 'border-green-500 bg-green-500 text-white'
                                      : 'border-gray-300 bg-white text-gray-400',
                            ]"
                            @click="goToStep(idx + 1)"
                        >
                            <span v-if="currentStep <= idx + 1">{{ idx + 1 }}</span>
                            <CheckCircle v-else class="h-5 w-5" />
                        </button>
                        <span
                            class="mt-1 w-24 text-center text-xs leading-tight"
                            :class="currentStep === idx + 1 ? 'font-semibold text-blue-600' : 'text-gray-400'"
                            >{{ label }}</span
                        >
                    </div>
                    <div v-if="idx < 2" class="mx-1 mb-5 h-0.5 flex-1" :class="currentStep > idx + 1 ? 'bg-green-400' : 'bg-gray-200'" />
                </template>
            </div>

            <!-- ── STEP 1: Download template ──────────────────────────────── -->
            <section v-if="currentStep === 1" class="space-y-4 rounded-xl border bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">الخطوة 1 — تحميل قالب Excel</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    حمّل القالب أدناه، أدخل بيانات الطلاب في الأعمدة المحددة، ثم انتقل للخطوة التالية لرفعه. لا تغيّر أسماء الأعمدة في الصف الأول.
                </p>

                <div class="overflow-x-auto rounded-lg border">
                    <table class="min-w-full text-sm">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-4 py-2 text-right font-medium">اسم العمود</th>
                                <th class="px-4 py-2 text-right font-medium">الوصف</th>
                                <th class="px-4 py-2 text-center font-medium">مطلوب</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(col, i) in columns" :key="col.name" :class="i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900'">
                                <td class="px-4 py-2 font-mono text-xs text-gray-700 dark:text-gray-300">{{ col.name }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ col.label }}</td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-block rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">مطلوب</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <a
                        :href="route('students.import.template')"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700"
                    >
                        تحميل قالب Excel
                    </a>
                    <button
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                        @click="goToStep(2)"
                    >
                        التالي ←
                    </button>
                </div>
            </section>

            <!-- ── STEP 2: Upload & Preview ───────────────────────────────── -->
            <section v-if="currentStep === 2" class="space-y-5 rounded-xl border bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">الخطوة 2 — رفع ومعاينة الملف</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">ارفع ملف Excel لمعاينة البيانات والتحقق من صحتها قبل اعتماد الاستيراد.</p>

                <div class="flex items-center gap-3">
                    <input ref="fileInputRef" type="file" accept=".xlsx,.xls" class="hidden" @change="onFileChange" />
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg border-2 border-dashed border-gray-300 px-6 py-3 text-sm text-gray-600 transition hover:border-blue-400"
                        @click="fileInputRef?.click()"
                    >
                        <span>{{ selectedFile ? selectedFile.name : 'اختر ملف xlsx أو xls' }}</span>
                    </button>
                    <button
                        :disabled="!selectedFile || excelForm.processing"
                        class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-amber-600 disabled:opacity-40"
                        @click="doPreview"
                    >
                        {{ excelForm.processing ? 'جارٍ المعاينة...' : 'معاينة' }}
                    </button>
                </div>
                <p v-if="excelForm.errors.file" class="text-sm text-red-600">{{ excelForm.errors.file }}</p>

                <template v-if="previewResult">
                    <div class="grid grid-cols-2 gap-3 text-center sm:grid-cols-4">
                        <div
                            v-for="card in summaryCards"
                            :key="card.label"
                            class="rounded-xl border p-4"
                            :class="{
                                'border-green-200 bg-green-50': card.color === 'green',
                                'border-amber-200 bg-amber-50': card.color === 'amber',
                                'border-red-200 bg-red-50': card.color === 'red',
                            }"
                        >
                            <div
                                class="text-2xl font-bold"
                                :class="{
                                    'text-green-700': card.color === 'green',
                                    'text-amber-700': card.color === 'amber',
                                    'text-red-600': card.color === 'red',
                                }"
                            >
                                {{ card.value }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500">{{ card.label }}</div>
                        </div>
                    </div>

                    <div v-if="previewResult.preview_rows && previewResult.preview_rows.length" class="overflow-x-auto rounded-lg border">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400">
                                <tr>
                                    <th class="px-3 py-2 text-right">#</th>
                                    <th class="px-3 py-2 text-right">الاسم</th>
                                    <th class="px-3 py-2 text-right">رقم القيد</th>
                                    <th class="px-3 py-2 text-right">القسم</th>
                                    <th class="px-3 py-2 text-center">الحالة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="row in previewResult.preview_rows" :key="row.row_number" :class="row.valid ? 'bg-white' : 'bg-red-50'">
                                    <td class="px-3 py-2 text-gray-400">{{ row.row_number }}</td>
                                    <td class="max-w-[160px] truncate px-3 py-2 font-medium text-gray-800">{{ row.full_name || '—' }}</td>
                                    <td class="px-3 py-2 font-mono text-gray-600">{{ row.registration_number || '—' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ row.department_code || '—' }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex items-center gap-1" :title="row.error ?? ''">
                                            <CheckCircle v-if="row.valid" class="h-4 w-4 text-green-500" />
                                            <XCircle v-else class="h-4 w-4 text-red-500" />
                                            {{ statusLabels[row.status] ?? row.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="previewResult.total_rows > 10" class="p-2 text-center text-xs text-gray-400">
                            يُعرض أول 10 صفوف فقط — إجمالي {{ previewResult.total_rows }} صف
                        </p>
                    </div>

                    <div v-if="previewResult.failed_rows.length" class="space-y-1.5 rounded-lg border border-red-200 bg-red-50 p-4">
                        <p class="mb-2 text-sm font-semibold text-red-700">الصفوف التي تحتوي على أخطاء:</p>
                        <div v-for="row in previewResult.failed_rows" :key="row.row_number" class="flex items-start gap-2 text-sm text-red-700">
                            <XCircle class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>صف {{ row.row_number }}: {{ row.reason }}</span>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button
                            v-if="previewResult.valid_count > 0"
                            class="rounded-lg bg-blue-700 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-blue-800"
                            @click="goToStep(3)"
                        >
                            المتابعة إلى تأكيد الاستيراد ←
                        </button>
                        <button class="rounded-lg bg-gray-100 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-200" @click="doPreview">
                            إعادة المعاينة
                        </button>
                    </div>
                </template>
            </section>

            <!-- ── STEP 3: Confirm import ──────────────────────────────────── -->
            <section v-if="currentStep === 3" class="space-y-5 rounded-xl border bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">الخطوة 3 — تأكيد الاستيراد</h2>

                <div v-if="previewResult" class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                    سيتم إنشاء <strong>{{ previewResult.valid_count }}</strong> حساب طالب جديد
                    <span v-if="previewResult.failed_count"> (سيُتخطى {{ previewResult.failed_count }} صف به أخطاء) </span>.
                    كل طالب سيحصل على حساب دخول برقم قيده وكلمة مرور مؤقتة، مع إجبار تغييرها عند أول دخول.
                </div>

                <div class="flex gap-3">
                    <button
                        :disabled="!selectedFile || excelForm.processing"
                        class="flex items-center gap-2 rounded-lg bg-blue-700 px-7 py-3 text-sm font-bold text-white transition hover:bg-blue-800 disabled:opacity-40"
                        @click="doImport"
                    >
                        <span v-if="excelForm.processing">جارٍ الاستيراد...</span>
                        <span v-else>تأكيد الاستيراد</span>
                    </button>
                    <button class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 transition hover:bg-gray-200" @click="goToStep(2)">
                        ← العودة
                    </button>
                </div>
            </section>

            <!-- ── STEP 4: Results ─────────────────────────────────────────── -->
            <section v-if="currentStep === 4 && importResult" class="space-y-5 rounded-xl border bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">نتيجة الاستيراد</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-green-200 bg-green-50 p-5 text-center">
                        <div class="text-4xl font-bold text-green-700">{{ importResult.success_count }}</div>
                        <div class="mt-1 text-sm text-gray-600">حساب طالب تم إنشاؤه</div>
                    </div>
                    <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-center">
                        <div class="text-4xl font-bold text-red-600">{{ importResult.failed_count }}</div>
                        <div class="mt-1 text-sm text-gray-600">صف فشل الاستيراد</div>
                    </div>
                </div>

                <div v-if="importResult.failed_rows.length" class="overflow-x-auto rounded-lg border border-red-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-red-50 text-red-700">
                            <tr>
                                <th class="px-4 py-2 text-right font-medium">رقم الصف</th>
                                <th class="px-4 py-2 text-right font-medium">سبب الخطأ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-100">
                            <tr v-for="row in importResult.failed_rows" :key="row.row_number" class="bg-white">
                                <td class="px-4 py-2 font-mono text-gray-600">{{ row.row_number }}</td>
                                <td class="px-4 py-2 text-red-700">{{ row.reason }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap gap-3 pt-1">
                    <a
                        :href="route('students.index')"
                        class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-800"
                    >
                        عرض قائمة الطلاب
                    </a>
                    <button class="rounded-lg bg-gray-100 px-5 py-2.5 text-sm text-gray-700 transition hover:bg-gray-200" @click="resetWizard">
                        استيراد ملف آخر
                    </button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
