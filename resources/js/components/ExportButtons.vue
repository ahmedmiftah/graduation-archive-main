<script setup lang="ts">
import { FileDown, FileSpreadsheet, Printer } from 'lucide-vue-next';

const props = defineProps<{
    pdfUrl: string;
    excelUrl: string;
    title?: string;
    subtitle?: string;
}>();

function printReport() {
    window.print();
}
</script>

<template>
    <div>
        <div class="no-print flex items-center gap-2">
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                @click="printReport"
            >
                <Printer :size="15" />
                طباعة
            </button>
            <a
                :href="pdfUrl"
                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 transition hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
            >
                <FileDown :size="15" />
                PDF
            </a>
            <a
                :href="excelUrl"
                class="inline-flex items-center gap-1.5 rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-sm font-medium text-green-700 transition hover:bg-green-100 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30"
            >
                <FileSpreadsheet :size="15" />
                Excel
            </a>
        </div>

        <div class="hidden print:mb-6 print:block print:border-b print:border-slate-200 print:pb-4">
            <div class="flex justify-center">
                <img src="/images/logo.png" alt="شعار الكلية" class="h-14 w-auto" />
            </div>
        </div>
    </div>
</template>
