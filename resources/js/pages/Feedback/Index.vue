<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<SharedData>();
const feedback = computed(() => page.props.feedback);
const filters = computed(() => page.props.filters ?? {});
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">لوحة الملاحظات</h1>
                    <p class="mt-1 text-sm text-slate-500">عرض جميع ملاحظات الزوار والإشعارات غير المقروءة.</p>
                </div>
                <Link
                    href="/feedback"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                    >تحديث</Link
                >
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-slate-500">المشروع</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-slate-500">الزائر</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-slate-500">نوع الملاحظة</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-slate-500">الحالة</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-slate-500">تاريخ الإرسال</th>
                            <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-slate-500">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="item in feedback.data" :key="item.id" class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ item.project_title }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ item.visitor_name || 'زائر مجهول' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ item.feedback_type }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ item.status }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ item.created_at }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-end text-sm">
                                <Link :href="route('feedback.show', { feedback: item.id })" class="text-primary hover:underline">عرض</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <span>إجمالي الملاحظات: {{ feedback.total }}</span>
                    <div class="space-x-2 rtl:space-x-reverse">
                        <Link
                            v-if="feedback.prev_page_url"
                            :href="feedback.prev_page_url"
                            class="rounded px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-100"
                            >السابق</Link
                        >
                        <Link
                            v-if="feedback.next_page_url"
                            :href="feedback.next_page_url"
                            class="rounded px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-100"
                            >التالي</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
