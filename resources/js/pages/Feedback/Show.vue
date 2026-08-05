<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';

const page = usePage<SharedData>();
const feedback = page.props.feedback;
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">تفاصيل الملاحظة</h1>
                    <p class="mt-1 text-sm text-slate-500">عرض تفاصيل الملاحظة وردود فريق الإدارة.</p>
                </div>
                <a
                    href="/feedback"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                    >رجوع</a
                >
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">المعلومات الأساسية</h2>
                    <dl class="mt-4 space-y-3 text-sm text-slate-700">
                        <div>
                            <dt class="font-semibold">المشروع</dt>
                            <dd>{{ feedback.project_title }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold">الزائر</dt>
                            <dd>{{ feedback.visitor_name || 'زائر مجهول' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold">البريد</dt>
                            <dd>{{ feedback.visitor_email || 'غير مدرج' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold">نوع الملاحظة</dt>
                            <dd>{{ feedback.feedback_type }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold">الحالة</dt>
                            <dd>{{ feedback.status }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold">التاريخ</dt>
                            <dd>{{ feedback.created_at }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <h2 class="text-lg font-semibold text-slate-900">نص الملاحظة</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-700">{{ feedback.message }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
