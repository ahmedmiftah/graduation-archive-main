<script setup lang="ts">
import Logo from '@/components/Brand/Logo.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const showBackToTop = ref(false);
const handleScroll = () => {
    showBackToTop.value = window.scrollY > 300;
};
const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});
onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

// Mock visitor counter for UI purposes
const visitorCount = ref(Math.floor(Math.random() * 5000) + 12450);

const props = defineProps<{
    auth?: { user?: { name: string } | null };
    stats?: { total_projects: number; total_departments: number; total_specializations: number };
}>();

const statsRows = computed(() => [
    { value: props.stats?.total_projects ?? 0, label: 'مشروع مؤرشف' },
    { value: props.stats?.total_departments ?? 0, label: 'قسم أكاديمي' },
    { value: props.stats?.total_specializations ?? 0, label: 'تخصص' },
]);

const features = [
    {
        title: 'أرشفة منظمة',
        desc: 'حفظ مشاريع التخرج بطريقة منظمة ومصنفة حسب القسم والتخصص والعام الدراسي.',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>`,
    },
    {
        title: 'بحث متقدم',
        desc: 'البحث السريع في الأرشيف بحسب العنوان أو اسم المشرف أو التخصص أو السنة.',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>`,
    },
    {
        title: 'تقارير وإحصائيات',
        desc: 'لوحة تحكم تفاعلية مع تقارير تفصيلية للمشاريع والتخصصات والمشرفين.',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>`,
    },
    {
        title: 'استيراد جماعي',
        desc: 'رفع وإدارة مشاريع متعددة دفعة واحدة عبر ملفات Excel مع دعم ملفات PDF.',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>`,
    },
];
</script>

<template>
    <Head title="كلية التقنية الإلكترونية — نظام أرشفة المشاريع" />

    <div class="min-h-screen bg-background font-body text-text-dark" dir="rtl">
        <!-- HEADER -->
        <header class="sticky top-0 z-50 border-b border-border bg-surface shadow-sm">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                <Logo size="md" />
                <div class="flex items-center gap-3">
                    <Link
                        v-if="auth?.user"
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 font-body text-sm font-medium text-white transition-colors hover:bg-primary-dark"
                    >
                        لوحة التحكم
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center rounded-lg border-2 border-primary px-5 py-2 font-body text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white"
                        >
                            تسجيل الدخول
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- HERO -->
        <section class="relative overflow-hidden bg-primary-dark">
            <!-- Subtle dot grid -->
            <div
                class="absolute inset-0 opacity-10"
                style="background-image: radial-gradient(circle, rgba(79, 168, 201, 0.6) 1px, transparent 1px); background-size: 28px 28px"
            ></div>

            <div class="relative mx-auto max-w-6xl px-6 py-24 text-center">
                <!-- College badge -->
                <div
                    class="mb-8 inline-flex items-center gap-2 rounded-full border border-primary-light/40 bg-primary/30 px-4 py-1.5 font-body text-sm text-primary-light"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-primary-light"></span>
                    كلية التقنية الإلكترونية — طرابلس
                </div>

                <!-- Main heading -->
                <h1 class="mb-6 font-display font-bold leading-tight text-white" style="font-size: clamp(1.6rem, 4vw, 2.6rem)">
                    نظام أرشفة وتصنيف<br />مشاريع التخرج
                </h1>

                <!-- Description -->
                <p class="mx-auto mb-10 max-w-2xl font-body text-base leading-relaxed text-primary-light/85">
                    منصة إلكترونية متكاملة لحفظ وأرشفة وتصنيف مشاريع التخرج بطريقة منظمة وسهلة الوصول، مع إمكانية البحث المتقدم وإصدار التقارير.
                </p>

                <!-- CTA buttons -->
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <Link
                        :href="route('public.browse')"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-light px-6 py-3 font-body text-sm font-semibold text-primary-dark shadow-lg transition-colors hover:bg-white"
                    >
                        تصفح المشاريع
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="shrink-0 rotate-180"
                        >
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </Link>
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-2 rounded-lg border-2 border-white/30 px-7 py-3 font-body text-sm font-medium text-white transition-colors hover:border-white/60 hover:bg-primary/30"
                    >
                        تسجيل الدخول
                    </Link>
                </div>

                <!-- Stats -->
                <div class="mx-auto mt-16 grid max-w-lg grid-cols-3 gap-4">
                    <div v-for="stat in statsRows" :key="stat.label" class="rounded-xl border border-white/15 bg-white/10 px-3 py-5">
                        <div class="mb-1 font-display text-xl font-bold text-white">{{ stat.value }}</div>
                        <div class="font-body text-xs leading-tight text-primary-light/75">{{ stat.label }}</div>
                    </div>
                </div>
            </div>

            <!-- Wave transition -->
            <svg viewBox="0 0 1440 48" preserveAspectRatio="none" class="-mb-px block w-full fill-current text-background" style="height: 48px">
                <path d="M0,48 C360,0 1080,48 1440,0 L1440,48 L0,48 Z" />
            </svg>
        </section>

        <!-- FEATURES -->
        <section id="features" class="bg-background py-20">
            <div class="mx-auto max-w-6xl px-6">
                <div class="mb-14 text-center">
                    <h2 class="mb-3 font-display text-xl font-bold text-text-dark">ما يقدمه النظام</h2>
                    <p class="mx-auto max-w-lg font-body leading-relaxed text-text-muted">
                        أدوات متكاملة تُمكّن الكلية من إدارة مشاريع التخرج بكفاءة عالية واحترافية
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="feat in features"
                        :key="feat.title"
                        class="group rounded-xl border border-border bg-surface p-6 transition-all duration-200 hover:border-primary/40 hover:shadow-md"
                    >
                        <div
                            class="mb-4 flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-white"
                            v-html="feat.icon"
                        ></div>
                        <h3 class="mb-2 font-display text-sm font-bold text-text-dark">{{ feat.title }}</h3>
                        <p class="font-body text-sm leading-relaxed text-text-muted">{{ feat.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA BAND -->
        <section class="bg-primary py-14">
            <div class="mx-auto max-w-xl px-6 text-center">
                <h2 class="mb-3 font-display text-xl font-bold text-white">جاهز للبدء؟</h2>
                <p class="mb-8 font-body text-sm leading-relaxed text-white/80">سجّل دخولك للوصول إلى الأرشيف الكامل وإدارة مشاريع التخرج.</p>
                <Link
                    :href="route('login')"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 font-body text-sm font-semibold text-primary shadow-md transition-colors hover:bg-background"
                >
                    تسجيل الدخول الآن
                </Link>
            </div>
        </section>

        <!-- FOOTER -->
        <!-- FOOTER -->
        <footer class="relative mt-auto border-t border-white/10 bg-[#111827] pb-6 pt-16 text-white" dir="rtl">
            <div class="mx-auto mb-12 grid max-w-7xl grid-cols-1 gap-12 px-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Section 1: Logo & Description -->
                <div class="flex flex-col gap-5">
                    <div class="flex items-center gap-4">
                        <img src="/images/logo.png" alt="شعار كلية التقنية الإلكترونية" class="h-12 w-auto object-contain" />
                        <div class="flex flex-col leading-tight">
                            <span class="font-display text-base font-bold text-white">كلية التقنية الإلكترونية</span>
                            <span class="font-body text-sm text-primary-light/70">نظام أرشفة مشاريع التخرج</span>
                        </div>
                    </div>
                    <p class="font-body text-sm leading-relaxed text-gray-400">
                        منصة إلكترونية متكاملة لحفظ وإدارة المشاريع الأكاديمية بكفاءة واحترافية عالية لدعم العملية التعليمية.
                    </p>
                    <div class="mt-2 flex items-center gap-3">
                        <a
                            href="#"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white/5 text-gray-400 transition-colors hover:bg-primary hover:text-white"
                            aria-label="Facebook"
                            ><svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" /></svg
                        ></a>
                        <a
                            href="#"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white/5 text-gray-400 transition-colors hover:bg-primary hover:text-white"
                            aria-label="X"
                            ><svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M18 6 6 18" />
                                <path d="M6 6l12 12" /></svg
                        ></a>
                        <a
                            href="#"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white/5 text-gray-400 transition-colors hover:bg-primary hover:text-white"
                            aria-label="LinkedIn"
                            ><svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z" />
                                <rect x="2" y="9" width="4" height="12" />
                                <circle cx="4" cy="4" r="2" /></svg
                        ></a>
                    </div>
                </div>

                <!-- Section 2: Quick Links -->
                <div class="flex flex-col gap-5">
                    <h3
                        class="relative w-max pb-2 font-display text-base font-bold text-white after:absolute after:bottom-0 after:right-0 after:h-0.5 after:w-1/2 after:bg-primary after:content-['']"
                    >
                        روابط سريعة
                    </h3>
                    <ul class="space-y-3 font-body text-sm text-gray-400">
                        <li>
                            <Link
                                :href="route('home')"
                                class="inline-block flex items-center gap-2 transition-all hover:-translate-x-1 hover:text-white"
                                ><span class="h-1.5 w-1.5 rounded-full bg-primary-light"></span>الرئيسية</Link
                            >
                        </li>
                        <li>
                            <Link
                                :href="route('public.browse')"
                                class="inline-block flex items-center gap-2 transition-all hover:-translate-x-1 hover:text-white"
                                ><span class="h-1.5 w-1.5 rounded-full bg-primary-light"></span>تصفح المشاريع</Link
                            >
                        </li>
                        <li>
                            <a href="#features" class="inline-block flex items-center gap-2 transition-all hover:-translate-x-1 hover:text-white"
                                ><span class="h-1.5 w-1.5 rounded-full bg-primary-light"></span>عن النظام (المميزات)</a
                            >
                        </li>
                        <li v-if="!auth?.user">
                            <Link
                                :href="route('login')"
                                class="inline-block flex items-center gap-2 transition-all hover:-translate-x-1 hover:text-white"
                                ><span class="h-1.5 w-1.5 rounded-full bg-primary-light"></span>تسجيل الدخول</Link
                            >
                        </li>
                        <li v-if="auth?.user">
                            <Link
                                :href="route('dashboard')"
                                class="inline-block flex items-center gap-2 transition-all hover:-translate-x-1 hover:text-white"
                                ><span class="h-1.5 w-1.5 rounded-full bg-primary-light"></span>لوحة التحكم</Link
                            >
                        </li>
                    </ul>
                </div>

                <!-- Section 3: Contact Info -->
                <div class="flex flex-col gap-5">
                    <h3
                        class="relative w-max pb-2 font-display text-base font-bold text-white after:absolute after:bottom-0 after:right-0 after:h-0.5 after:w-1/2 after:bg-primary after:content-['']"
                    >
                        معلومات التواصل
                    </h3>
                    <ul class="space-y-4 font-body text-sm text-gray-400">
                        <li class="flex items-start gap-3">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="mt-0.5 shrink-0 text-primary-light"
                            >
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"
                                />
                            </svg>
                            <span dir="ltr">+218 21 000 0000</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="mt-0.5 shrink-0 text-primary-light"
                            >
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                <polyline points="22,6 12,13 2,6" />
                            </svg>
                            <span dir="ltr">info@cet.edu.ly</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="mt-0.5 shrink-0 text-primary-light"
                            >
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                            <span>طرابلس — ليبيا<br />بن عاشور، كلية التقنية الإلكترونية</span>
                        </li>
                    </ul>
                </div>

                <!-- Section 4: Dev Team -->
                <div class="flex flex-col gap-5">
                    <h3
                        class="relative w-max pb-2 font-display text-base font-bold text-white after:absolute after:bottom-0 after:right-0 after:h-0.5 after:w-1/2 after:bg-primary after:content-['']"
                    >
                        فريق التطوير
                    </h3>

                    <div class="mt-4 flex flex-wrap items-stretch justify-center gap-2">
                        <div
                            class="w-full min-w-[180px] max-w-[210px] rounded-2xl border border-white/10 bg-white/5 p-3 text-center shadow-sm transition-colors hover:bg-primary/20 sm:w-auto"
                        >
                            <div
                                class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-gray-300"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="12" cy="8" r="4" />
                                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-body text-[11px] font-bold text-white">[المطور الأول]</div>
                                <div class="mt-1 font-body text-[10px] text-gray-400">مطوّر</div>
                            </div>
                        </div>

                        <div
                            class="w-full min-w-[180px] max-w-[210px] rounded-2xl border border-white/10 bg-white/5 p-3 text-center shadow-sm transition-colors hover:bg-primary/20 sm:w-auto"
                        >
                            <div
                                class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-gray-300"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="12" cy="8" r="4" />
                                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-body text-[11px] font-bold text-white">[المطور الثاني]</div>
                                <div class="mt-1 font-body text-[10px] text-gray-400">مطوّر</div>
                            </div>
                        </div>

                        <div
                            class="w-full min-w-[180px] max-w-[210px] rounded-2xl border border-white/10 bg-white/5 p-3 text-center shadow-sm transition-colors hover:bg-primary/20 sm:w-auto"
                        >
                            <div
                                class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-gray-300"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="12" cy="8" r="4" />
                                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-body text-[11px] font-bold text-white">[المطور الثالث]</div>
                                <div class="mt-1 font-body text-[10px] text-gray-400">مطوّر</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright & Visitor Counter -->
            <div class="border-t border-white/10 pt-6">
                <div class="relative mx-auto flex min-h-[40px] max-w-7xl flex-col items-center justify-center gap-4 px-6">
                    <!-- Copyright -->
                    <p class="order-2 text-center font-body text-sm text-gray-400 md:order-1">
                        &copy; {{ new Date().getFullYear() }} كلية التقنية الإلكترونية - طرابلس. جميع الحقوق محفوظة.
                    </p>

                    <!-- Visitor Counter -->
                    <div
                        class="order-1 flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-4 py-2 shadow-inner md:absolute md:left-6 md:top-1/2 md:order-2 md:-translate-y-1/2"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="text-primary-light"
                        >
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <span class="font-body text-sm text-gray-400"
                            >إجمالي الزيارات: <strong class="ml-1 font-mono text-white" dir="ltr">{{ visitorCount.toLocaleString() }}</strong></span
                        >
                    </div>
                </div>
            </div>

            <!-- Back to Top Button -->
            <button
                @click="scrollToTop"
                :class="[
                    'fixed bottom-8 left-8 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-primary text-white shadow-xl shadow-primary/30 transition-all duration-300 hover:-translate-y-1 hover:bg-primary-dark focus:outline-none',
                    showBackToTop ? 'translate-y-0 opacity-100' : 'pointer-events-none translate-y-8 opacity-0',
                ]"
                aria-label="العودة للأعلى"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="m18 15-6-6-6 6" />
                </svg>
            </button>
        </footer>
    </div>
</template>
