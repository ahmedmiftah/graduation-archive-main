<script setup lang="ts">
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import Modal from '@/components/Modal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { ideaStatusColor, ideaStatusLabel } from '@/lib/ideaStatusBadge';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Specialization {
    id: number;
    name: string;
    department_id: number;
}

interface Idea {
    id: number;
    title: string;
    description: string;
    specialization_id: number;
    specialization: Specialization | null;
    required_students_count: number;
    skills: string | null;
    keywords: string | null;
    notes: string | null;
    status: 'available' | 'reserved' | 'completed' | 'closed';
    pending_requests_count: number;
}

const props = defineProps<{
    ideas: Idea[];
    specializations: Specialization[];
}>();

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/supervisor/dashboard' },
    { title: 'أفكار المشاريع', href: '/supervisor/ideas' },
];

const STATUSES: Idea['status'][] = ['available', 'reserved', 'completed', 'closed'];

// ── Create / edit modal ─────────────────────────────────────────────
const showModal = ref(false);
const editingIdea = ref<Idea | null>(null);
const form = useForm({
    title: '',
    description: '',
    specialization_id: '' as number | '',
    required_students_count: 1,
    skills: '',
    keywords: '',
    notes: '',
    status: 'available' as Idea['status'],
});

function openCreate() {
    editingIdea.value = null;
    form.reset();
    form.required_students_count = 1;
    form.status = 'available';
    showModal.value = true;
}

function openEdit(idea: Idea) {
    editingIdea.value = idea;
    form.title = idea.title;
    form.description = idea.description;
    form.specialization_id = idea.specialization_id;
    form.required_students_count = idea.required_students_count;
    form.skills = idea.skills ?? '';
    form.keywords = idea.keywords ?? '';
    form.notes = idea.notes ?? '';
    form.status = idea.status;
    showModal.value = true;
}

function submit() {
    if (editingIdea.value) {
        form.put(route('supervisor.ideas.update', editingIdea.value.id), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('supervisor.ideas.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
}

// ── Delete ───────────────────────────────────────────────────────────
const confirmDeleteIdea = ref<Idea | null>(null);

function deleteIdea() {
    if (!confirmDeleteIdea.value) return;
    router.delete(route('supervisor.ideas.destroy', confirmDeleteIdea.value.id), {
        onFinish: () => (confirmDeleteIdea.value = null),
    });
}
</script>

<template>
    <Head title="أفكار المشاريع" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4" dir="rtl">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">أفكار المشاريع التي نشرتها</h1>
                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    @click="openCreate"
                >
                    + نشر فكرة جديدة
                </button>
            </div>

            <div v-if="flash.success" class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                {{ flash.error }}
            </div>

            <div v-if="props.ideas.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">لم تنشر أي فكرة مشروع بعد.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    v-for="idea in props.ideas"
                    :key="idea.id"
                    class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ idea.title }}</h2>
                        <span :class="['shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium', ideaStatusColor(idea.status)]">
                            {{ ideaStatusLabel(idea.status) }}
                        </span>
                    </div>
                    <p class="mt-2 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ idea.description }}</p>
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                        <span>التخصص: <span class="font-medium text-gray-700 dark:text-gray-300">{{ idea.specialization?.name ?? '—' }}</span></span>
                        <span>عدد الطلاب: <span class="font-medium text-gray-700 dark:text-gray-300">{{ idea.required_students_count }}</span></span>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <Link
                            :href="route('supervisor.ideas.requests.index', idea.id)"
                            class="relative rounded bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-200 dark:bg-blue-900/20 dark:text-blue-400"
                        >
                            مراجعة الطلبات
                            <span v-if="idea.pending_requests_count > 0" class="ms-1 rounded-full bg-blue-600 px-1.5 py-0.5 text-[0.65rem] font-semibold text-white">
                                {{ idea.pending_requests_count }}
                            </span>
                        </Link>
                        <button
                            type="button"
                            class="rounded bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400"
                            @click="openEdit(idea)"
                        >
                            تعديل
                        </button>
                        <button
                            type="button"
                            class="rounded bg-red-100 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400"
                            @click="confirmDeleteIdea = idea"
                        >
                            حذف
                        </button>
                    </div>
                </div>
            </div>

            <!-- Create / Edit Modal -->
            <Modal :show="showModal" :title="editingIdea ? 'تعديل فكرة المشروع' : 'نشر فكرة مشروع جديدة'" @close="showModal = false">
                <form id="idea-form" class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">العنوان</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-500': form.errors.title }"
                        />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الوصف</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-500': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">التخصص</label>
                            <select
                                v-model="form.specialization_id"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'border-red-500': form.errors.specialization_id }"
                            >
                                <option value="" disabled>اختر التخصص</option>
                                <option v-for="s in props.specializations" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p v-if="form.errors.specialization_id" class="mt-1 text-xs text-red-600">{{ form.errors.specialization_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">عدد الطلاب المطلوب</label>
                            <input
                                v-model.number="form.required_students_count"
                                type="number"
                                min="1"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'border-red-500': form.errors.required_students_count }"
                            />
                            <p v-if="form.errors.required_students_count" class="mt-1 text-xs text-red-600">{{ form.errors.required_students_count }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">المهارات المطلوبة</label>
                        <input
                            v-model="form.skills"
                            type="text"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">كلمات مفتاحية</label>
                        <input
                            v-model="form.keywords"
                            type="text"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ملاحظات</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>
                    <div v-if="editingIdea">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الحالة</label>
                        <select
                            v-model="form.status"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option v-for="s in STATUSES" :key="s" :value="s">{{ ideaStatusLabel(s) }}</option>
                        </select>
                    </div>
                </form>
                <template #footer>
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300"
                        @click="showModal = false"
                    >
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        form="idea-form"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        حفظ
                    </button>
                </template>
            </Modal>

            <ConfirmDelete
                :show="!!confirmDeleteIdea"
                :item-name="confirmDeleteIdea?.title"
                @confirmed="deleteIdea"
                @cancelled="confirmDeleteIdea = null"
            />
        </div>
    </AppLayout>
</template>
