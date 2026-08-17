<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Specialization {
    id: number;
    name: string;
    department_id: number;
}
interface FacultyMember {
    id: number;
    full_name: string;
}
interface Idea {
    id: number;
    title: string;
    description: string;
    specialization: Specialization | null;
    facultyMember: FacultyMember | null;
    required_students_count: number;
    skills: string | null;
}
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
interface Paginated {
    data: Idea[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    ideas: Paginated;
    specializations: Specialization[];
    filters: { specialization_id?: string };
}>();

const specializationId = ref(props.filters.specialization_id ?? '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/student/dashboard' },
    { title: 'أفكار المشاريع', href: '/student/ideas' },
];

function applyFilter() {
    router.get(
        route('student.ideas.index'),
        { specialization_id: specializationId.value || undefined },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <Head title="أفكار المشاريع" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">تصفّح أفكار المشاريع المتاحة</h1>
                <select
                    v-model="specializationId"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    @change="applyFilter"
                >
                    <option value="">كل التخصصات</option>
                    <option v-for="s in props.specializations" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">إجمالي الأفكار المتاحة: {{ props.ideas.total }}</p>

            <div v-if="props.ideas.data.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">لا توجد أفكار متاحة حاليًا.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="idea in props.ideas.data"
                    :key="idea.id"
                    :href="route('student.ideas.show', idea.id)"
                    class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 transition-colors hover:border-blue-400 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2 class="line-clamp-2 font-semibold text-gray-900 dark:text-gray-100">{{ idea.title }}</h2>
                    <p class="mt-2 line-clamp-3 flex-1 text-sm text-gray-600 dark:text-gray-400">{{ idea.description }}</p>
                    <div class="mt-3 space-y-1 text-xs text-gray-500 dark:text-gray-400">
                        <p>التخصص: <span class="font-medium text-gray-700 dark:text-gray-300">{{ idea.specialization?.name ?? '—' }}</span></p>
                        <p>المشرف: <span class="font-medium text-gray-700 dark:text-gray-300">{{ idea.facultyMember?.full_name ?? '—' }}</span></p>
                        <p>عدد الطلاب المطلوب: <span class="font-medium text-gray-700 dark:text-gray-300">{{ idea.required_students_count }}</span></p>
                    </div>
                </Link>
            </div>

            <div v-if="props.ideas.last_page > 1" class="mt-4 flex flex-wrap justify-center gap-1">
                <template v-for="link in props.ideas.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg border px-3 py-1.5 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'border-blue-600 bg-blue-600 text-white'
                                : 'border-gray-300 text-gray-600 hover:border-blue-400 dark:border-gray-600 dark:text-gray-400'
                        "
                        v-html="link.label"
                    />
                    <span v-else class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-400 opacity-50 dark:border-gray-600" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
