<script setup lang="ts">
import Logo from '@/components/Brand/Logo.vue';

const colors = [
    { name: 'primary', label: 'الأساسي', hex: '#1B6B93', class: 'bg-primary', textClass: 'text-white' },
    { name: 'primary-dark', label: 'الأساسي الداكن', hex: '#103A52', class: 'bg-primary-dark', textClass: 'text-white' },
    { name: 'primary-light', label: 'الأساسي الفاتح', hex: '#4FA8C9', class: 'bg-primary-light', textClass: 'text-white' },
    { name: 'surface', label: 'السطح', hex: '#FFFFFF', class: 'bg-surface border border-border', textClass: 'text-text-dark' },
    { name: 'background', label: 'الخلفية', hex: '#F7F9FA', class: 'bg-background border border-border', textClass: 'text-text-dark' },
    { name: 'border', label: 'الحدود', hex: '#E2E8ED', class: 'bg-border', textClass: 'text-text-dark' },
    { name: 'text-dark', label: 'النص الداكن', hex: '#0E2433', class: 'bg-text-dark', textClass: 'text-white' },
    { name: 'text-muted', label: 'النص الخافت', hex: '#5C7689', class: 'bg-text-muted', textClass: 'text-white' },
];

const typographyScale = [
    { label: 'عنوان كبير', class: 'font-display text-4xl font-bold text-text-dark', sample: 'نظام أرشفة مشاريع التخرج' },
    { label: 'عنوان متوسط', class: 'font-display text-2xl font-bold text-text-dark', sample: 'كلية التقنية الإلكترونية' },
    { label: 'عنوان صغير', class: 'font-display text-xl font-semibold text-text-dark', sample: 'إدارة المشاريع والتخصصات' },
    { label: 'نص أساسي', class: 'font-body text-base text-text-dark', sample: 'هذا مثال على النص الأساسي المستخدم في محتوى الصفحات والنماذج.' },
    { label: 'نص خافت', class: 'font-body text-sm text-text-muted', sample: 'نص مساعد يستخدم للتفاصيل والتلميحات الثانوية.' },
    { label: 'نص صغير', class: 'font-body text-xs text-text-muted', sample: 'بيانات وصفية — التاريخ، الحالة، رقم القيد' },
];

const buttons = [
    { label: 'أساسي', class: 'bg-primary hover:bg-primary-dark text-white', variant: 'primary' },
    { label: 'داكن', class: 'bg-primary-dark hover:bg-primary text-white', variant: 'dark' },
    { label: 'فاتح', class: 'bg-primary-light hover:bg-primary text-white', variant: 'light' },
    { label: 'محدد', class: 'border-2 border-primary text-primary hover:bg-primary hover:text-white bg-transparent', variant: 'outline' },
    { label: 'خافت', class: 'bg-background border border-border text-text-dark hover:border-primary hover:text-primary', variant: 'ghost' },
    { label: 'خطر', class: 'bg-red-600 hover:bg-red-700 text-white', variant: 'danger' },
];
</script>

<template>
    <div class="min-h-screen bg-background font-body" dir="rtl">
        <!-- Header -->
        <div class="flex items-center justify-between bg-primary-dark px-8 py-6 text-white">
            <div>
                <h1 class="font-display text-2xl font-bold">Design System</h1>
                <p class="mt-1 text-sm text-primary-light">صفحة مرجعية مؤقتة — للمراجعة فقط</p>
            </div>
            <Logo size="md" />
        </div>

        <div class="mx-auto max-w-5xl space-y-14 px-8 py-10">
            <!-- Logo Section -->
            <section>
                <h2 class="mb-1 font-display text-xl font-bold text-text-dark">الشعار</h2>
                <p class="mb-6 text-sm text-text-muted">ثلاثة أحجام — sm / md / lg</p>
                <div class="flex flex-wrap items-end gap-10 rounded-xl border border-border bg-surface p-8">
                    <div class="flex flex-col items-center gap-3">
                        <Logo size="sm" />
                        <span class="font-body text-xs text-text-muted">sm</span>
                    </div>
                    <div class="flex flex-col items-center gap-3">
                        <Logo size="md" />
                        <span class="font-body text-xs text-text-muted">md</span>
                    </div>
                    <div class="flex flex-col items-center gap-3">
                        <Logo size="lg" />
                        <span class="font-body text-xs text-text-muted">lg</span>
                    </div>
                    <div class="flex flex-col items-center gap-3 rounded-lg bg-primary-dark p-4">
                        <Logo size="md" />
                        <span class="font-body text-xs text-primary-light">على خلفية داكنة</span>
                    </div>
                </div>
            </section>

            <!-- Colors Section -->
            <section>
                <h2 class="mb-1 font-display text-xl font-bold text-text-dark">لوحة الألوان</h2>
                <p class="mb-6 text-sm text-text-muted">توكنات الألوان المستخدمة في كامل النظام</p>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div v-for="color in colors" :key="color.name" class="overflow-hidden rounded-xl border border-border shadow-sm">
                        <div :class="['h-20 w-full', color.class]"></div>
                        <div class="bg-surface px-3 py-2">
                            <p class="font-body text-sm font-medium text-text-dark">{{ color.label }}</p>
                            <p class="mt-0.5 font-body text-xs text-text-muted">{{ color.hex }}</p>
                            <p class="font-body text-xs text-text-muted">{{ color.name }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Typography Section -->
            <section>
                <h2 class="mb-1 font-display text-xl font-bold text-text-dark">الطباعة</h2>
                <p class="mb-6 text-sm text-text-muted">
                    <span class="font-medium text-primary">Tajawal</span> للعناوين &nbsp;·&nbsp;
                    <span class="font-medium text-primary">IBM Plex Sans Arabic</span> للنص الأساسي
                </p>
                <div class="divide-y divide-border rounded-xl border border-border bg-surface">
                    <div v-for="item in typographyScale" :key="item.label" class="flex items-center gap-6 px-6 py-4">
                        <span class="w-28 flex-shrink-0 text-left font-body text-xs text-text-muted">{{ item.label }}</span>
                        <span :class="item.class">{{ item.sample }}</span>
                    </div>
                </div>
            </section>

            <!-- Buttons Section -->
            <section>
                <h2 class="mb-1 font-display text-xl font-bold text-text-dark">أزرار</h2>
                <p class="mb-6 text-sm text-text-muted">متغيرات الأزرار الأساسية</p>
                <div class="flex flex-wrap items-center gap-4 rounded-xl border border-border bg-surface p-8">
                    <button
                        v-for="btn in buttons"
                        :key="btn.variant"
                        :class="['cursor-default rounded-lg px-5 py-2.5 font-body text-sm font-medium transition-colors duration-150', btn.class]"
                    >
                        {{ btn.label }}
                    </button>
                </div>

                <!-- Button sizes -->
                <div class="mt-4 flex flex-wrap items-end gap-4 rounded-xl border border-border bg-surface p-8">
                    <button class="cursor-default rounded-md bg-primary px-3 py-1.5 font-body text-xs text-white">صغير</button>
                    <button class="cursor-default rounded-lg bg-primary px-5 py-2.5 font-body text-sm text-white">متوسط</button>
                    <button class="cursor-default rounded-lg bg-primary px-6 py-3 font-body text-base text-white">كبير</button>
                </div>
            </section>

            <!-- Spacing / Borders Section -->
            <section>
                <h2 class="mb-1 font-display text-xl font-bold text-text-dark">بطاقات وحدود</h2>
                <p class="mb-6 text-sm text-text-muted">أنماط البطاقات والحدود</p>
                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-lg border border-border bg-surface p-5 shadow-sm">
                        <p class="font-display text-sm font-bold text-text-dark">بطاقة عادية</p>
                        <p class="mt-1 font-body text-xs text-text-muted">border + shadow-sm</p>
                    </div>
                    <div class="rounded-lg border-2 border-primary bg-surface p-5">
                        <p class="font-display text-sm font-bold text-primary">بطاقة محددة</p>
                        <p class="mt-1 font-body text-xs text-text-muted">border-2 border-primary</p>
                    </div>
                    <div class="rounded-lg border border-primary/30 bg-primary/10 p-5">
                        <p class="font-display text-sm font-bold text-primary-dark">بطاقة ملونة</p>
                        <p class="mt-1 font-body text-xs text-text-muted">bg-primary/10</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Footer notice -->
        <div class="border-t border-border bg-surface py-4 text-center">
            <p class="font-body text-xs text-text-muted">هذه الصفحة مؤقتة للمراجعة — يجب حذفها قبل الإنتاج</p>
        </div>
    </div>
</template>
