<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Logo from '@/components/Brand/Logo.vue';

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
        desc: 'لوحة تحكم تفاعلية مع تقارير تفصيلية للمشاريع والأقسام والمشرفين.',
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
        <header class="sticky top-0 z-50 bg-surface border-b border-border shadow-sm">
            <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
                <Logo size="md" />
                <div class="flex items-center gap-3">
                    <Link
                        v-if="auth?.user"
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-primary text-white font-body text-sm font-medium hover:bg-primary-dark transition-colors"
                    >
                        لوحة التحكم
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center px-5 py-2 rounded-lg border-2 border-primary text-primary font-body text-sm font-medium hover:bg-primary hover:text-white transition-colors"
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
                style="background-image: radial-gradient(circle, rgba(79,168,201,0.6) 1px, transparent 1px); background-size: 28px 28px;"
            ></div>

            <div class="relative max-w-6xl mx-auto px-6 py-24 text-center">
                <!-- College badge -->
                <div class="inline-flex items-center gap-2 bg-primary/30 border border-primary-light/40 text-primary-light rounded-full px-4 py-1.5 text-sm font-body mb-8">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary-light"></span>
                    كلية التقنية الإلكترونية — طرابلس
                </div>

                <!-- Main heading -->
                <h1 class="font-display font-bold text-white leading-tight mb-6" style="font-size: clamp(1.6rem, 4vw, 2.6rem);">
                    نظام أرشفة وتصنيف<br>مشاريع التخرج
                </h1>

                <!-- Description -->
                <p class="font-body text-primary-light/85 text-base mb-10 max-w-2xl mx-auto leading-relaxed">
                    منصة إلكترونية متكاملة لحفظ وأرشفة وتصنيف مشاريع التخرج بطريقة منظمة وسهلة الوصول،
                    مع إمكانية البحث المتقدم وإصدار التقارير.
                </p>

                <!-- CTA buttons -->
                <div class="flex items-center justify-center flex-wrap gap-4">
                    <Link
                        :href="route('public.browse')"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-primary-light text-primary-dark font-body font-semibold text-sm hover:bg-white transition-colors shadow-lg"
                    >
                        تصفح المشاريع
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="rotate-180 shrink-0"><path d="m9 18 6-6-6-6"/></svg>
                    </Link>
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-2 px-7 py-3 rounded-lg border-2 border-white/30 text-white font-body font-medium text-sm hover:border-white/60 hover:bg-primary/30 transition-colors"
                    >
                        تسجيل الدخول
                    </Link>
                </div>

                <!-- Stats -->
                <div class="mt-16 grid grid-cols-3 gap-4 max-w-lg mx-auto">
                    <div
                        v-for="stat in statsRows"
                        :key="stat.label"
                        class="bg-white/10 border border-white/15 rounded-xl py-5 px-3"
                    >
                        <div class="font-display font-bold text-xl text-white mb-1">{{ stat.value }}</div>
                        <div class="font-body text-xs text-primary-light/75 leading-tight">{{ stat.label }}</div>
                    </div>
                </div>
            </div>

            <!-- Wave transition -->
            <svg viewBox="0 0 1440 48" preserveAspectRatio="none" class="w-full block fill-current text-background -mb-px" style="height:48px;">
                <path d="M0,48 C360,0 1080,48 1440,0 L1440,48 L0,48 Z"/>
            </svg>
        </section>

        <!-- FEATURES -->
        <section id="features" class="py-20 bg-background">
            <div class="max-w-6xl mx-auto px-6">
                <div class="text-center mb-14">
                    <h2 class="font-display font-bold text-xl text-text-dark mb-3">ما يقدمه النظام</h2>
                    <p class="font-body text-text-muted max-w-lg mx-auto leading-relaxed">
                        أدوات متكاملة تُمكّن الكلية من إدارة مشاريع التخرج بكفاءة عالية واحترافية
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div
                        v-for="feat in features"
                        :key="feat.title"
                        class="bg-surface border border-border rounded-xl p-6 hover:border-primary/40 hover:shadow-md transition-all duration-200 group"
                    >
                        <div
                            class="w-11 h-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-4 group-hover:bg-primary group-hover:text-white transition-colors shrink-0"
                            v-html="feat.icon"
                        ></div>
                        <h3 class="font-display font-bold text-sm text-text-dark mb-2">{{ feat.title }}</h3>
                        <p class="font-body text-sm text-text-muted leading-relaxed">{{ feat.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA BAND -->
        <section class="bg-primary py-14">
            <div class="max-w-xl mx-auto px-6 text-center">
                <h2 class="font-display font-bold text-xl text-white mb-3">جاهز للبدء؟</h2>
                <p class="font-body text-white/80 mb-8 leading-relaxed text-sm">
                    سجّل دخولك للوصول إلى الأرشيف الكامل وإدارة مشاريع التخرج.
                </p>
                <Link
                    :href="route('login')"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white text-primary font-body font-semibold text-sm hover:bg-background transition-colors shadow-md"
                >
                    تسجيل الدخول الآن
                </Link>
            </div>
        </section>

        <!-- FOOTER -->
        <!-- FOOTER -->
        <footer class="bg-[#111827] text-white pt-16 pb-6 border-t border-white/10 mt-auto relative" dir="rtl">
            <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                
                <!-- Section 1: Logo & Description -->
                <div class="flex flex-col gap-5">
                    <div class="flex items-center gap-4">
                        <img src="/images/logo.png" alt="شعار كلية التقنية الإلكترونية" class="h-12 w-auto object-contain" />
                        <div class="flex flex-col leading-tight">
                            <span class="font-display font-bold text-white text-base">كلية التقنية الإلكترونية</span>
                            <span class="font-body text-primary-light/70 text-sm">نظام أرشفة مشاريع التخرج</span>
                        </div>
                    </div>
                    <p class="font-body text-sm text-gray-400 leading-relaxed">
                        منصة إلكترونية متكاملة لحفظ وإدارة المشاريع الأكاديمية بكفاءة واحترافية عالية لدعم العملية التعليمية.
                    </p>
                    <div class="flex items-center gap-3 mt-2">
                        <a href="#" class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-gray-400 hover:bg-primary hover:text-white transition-colors" aria-label="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-gray-400 hover:bg-primary hover:text-white transition-colors" aria-label="X"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-gray-400 hover:bg-primary hover:text-white transition-colors" aria-label="LinkedIn"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a>
                    </div>
                </div>

                <!-- Section 2: Quick Links -->
                <div class="flex flex-col gap-5">
                    <h3 class="text-base font-bold font-display text-white relative w-max pb-2 after:content-[''] after:absolute after:bottom-0 after:right-0 after:h-0.5 after:w-1/2 after:bg-primary">روابط سريعة</h3>
                    <ul class="space-y-3 text-sm text-gray-400 font-body">
                        <li><Link :href="route('home')" class="hover:text-white hover:-translate-x-1 inline-block transition-all flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-primary-light"></span>الرئيسية</Link></li>
                        <li><Link :href="route('public.browse')" class="hover:text-white hover:-translate-x-1 inline-block transition-all flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-primary-light"></span>تصفح المشاريع</Link></li>
                        <li><a href="#features" class="hover:text-white hover:-translate-x-1 inline-block transition-all flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-primary-light"></span>عن النظام (المميزات)</a></li>
                        <li v-if="!auth?.user"><Link :href="route('login')" class="hover:text-white hover:-translate-x-1 inline-block transition-all flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-primary-light"></span>تسجيل الدخول</Link></li>
                        <li v-if="auth?.user"><Link :href="route('dashboard')" class="hover:text-white hover:-translate-x-1 inline-block transition-all flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-primary-light"></span>لوحة التحكم</Link></li>
                    </ul>
                </div>

                <!-- Section 3: Contact Info -->
                <div class="flex flex-col gap-5">
                    <h3 class="text-base font-bold font-display text-white relative w-max pb-2 after:content-[''] after:absolute after:bottom-0 after:right-0 after:h-0.5 after:w-1/2 after:bg-primary">معلومات التواصل</h3>
                    <ul class="space-y-4 text-sm text-gray-400 font-body">
                        <li class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary-light shrink-0 mt-0.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <span dir="ltr">+218 21 000 0000</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary-light shrink-0 mt-0.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span dir="ltr">info@cet.edu.ly</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary-light shrink-0 mt-0.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>طرابلس — ليبيا<br>بن عاشور، كلية التقنية الإلكترونية</span>
                        </li>
                    </ul>
                </div>

                <!-- Section 4: Dev Team -->
                <div class="flex flex-col gap-5">
                    <h3 class="text-base font-bold font-display text-white relative w-max pb-2 after:content-[''] after:absolute after:bottom-0 after:right-0 after:h-0.5 after:w-1/2 after:bg-primary">فريق التطوير</h3>
                    
                    <div class="flex flex-wrap items-stretch justify-center gap-2 mt-4">
                        <div class="w-full sm:w-auto bg-white/5 rounded-2xl p-3 border border-white/10 shadow-sm hover:bg-primary/20 transition-colors text-center min-w-[180px] max-w-[210px]">
                            <div class="mx-auto w-10 h-10 rounded-full bg-white/10 border border-white/20 flex items-center justify-center mb-2 text-gray-300">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                            </div>
                            <div>
                                <div class="text-white text-[11px] font-bold font-body">[المطور الأول]</div>
                                <div class="text-gray-400 text-[10px] font-body mt-1">مطوّر</div>
                            </div>
                        </div>

                        <div class="w-full sm:w-auto bg-white/5 rounded-2xl p-3 border border-white/10 shadow-sm hover:bg-primary/20 transition-colors text-center min-w-[180px] max-w-[210px]">
                            <div class="mx-auto w-10 h-10 rounded-full bg-white/10 border border-white/20 flex items-center justify-center mb-2 text-gray-300">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                            </div>
                            <div>
                                <div class="text-white text-[11px] font-bold font-body">[المطور الثاني]</div>
                                <div class="text-gray-400 text-[10px] font-body mt-1">مطوّر</div>
                            </div>
                        </div>

                        <div class="w-full sm:w-auto bg-white/5 rounded-2xl p-3 border border-white/10 shadow-sm hover:bg-primary/20 transition-colors text-center min-w-[180px] max-w-[210px]">
                            <div class="mx-auto w-10 h-10 rounded-full bg-white/10 border border-white/20 flex items-center justify-center mb-2 text-gray-300">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                            </div>
                            <div>
                                <div class="text-white text-[11px] font-bold font-body">[المطور الثالث]</div>
                                <div class="text-gray-400 text-[10px] font-body mt-1">مطوّر</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright & Visitor Counter -->
            <div class="border-t border-white/10 pt-6">
                <div class="max-w-7xl mx-auto px-6 flex flex-col items-center justify-center gap-4 relative min-h-[40px]">
                    <!-- Copyright -->
                    <p class="font-body text-sm text-gray-400 text-center order-2 md:order-1">
                        &copy; {{ new Date().getFullYear() }} كلية التقنية الإلكترونية - طرابلس. جميع الحقوق محفوظة.
                    </p>
                    
                    <!-- Visitor Counter -->
                    <div class="flex items-center gap-2 bg-white/5 rounded-lg px-4 py-2 border border-white/10 shadow-inner order-1 md:order-2 md:absolute md:left-6 md:top-1/2 md:-translate-y-1/2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary-light"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span class="text-sm font-body text-gray-400">إجمالي الزيارات: <strong class="text-white font-mono ml-1" dir="ltr">{{ visitorCount.toLocaleString() }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Back to Top Button -->
            <button 
                @click="scrollToTop"
                :class="['fixed bottom-8 left-8 w-12 h-12 flex items-center justify-center rounded-full bg-primary text-white shadow-xl shadow-primary/30 transition-all duration-300 z-50 focus:outline-none hover:bg-primary-dark hover:-translate-y-1', showBackToTop ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8 pointer-events-none']"
                aria-label="العودة للأعلى"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
            </button>
        </footer>

    </div>
</template>
