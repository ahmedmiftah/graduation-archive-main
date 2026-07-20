<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

interface Props {
    show: boolean;
    projectId: number | null;
    onClose: () => void;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    projectId: null,
    onClose: () => {},
});

const emit = defineEmits<{
    submitted: [];
}>();

const form = useForm({
    visitor_name: '',
    visitor_email: '',
    feedback_type: 'suggestion',
    title: '',
    message: '',
    rating: null as number | null,
});

const ratingOptions = [5, 4, 3, 2, 1];

watch(
    () => props.show,
    (value) => {
        if (!value) {
            form.reset();
        }
    },
);

function submit() {
    if (props.projectId === null) {
        return;
    }

    form.post(route('projects.feedback.store', { id: props.projectId }), {
        preserveState: true,
        onSuccess: () => {
            emit('submitted');
            props.onClose();
        },
    });
}
</script>

<template>
    <Modal :show="props.show" title="إرسال ملاحظة" @close="props.onClose">
        <form @submit.prevent="submit" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الاسم (اختياري)</label>
                    <input
                        v-model="form.visitor_name"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-right text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-primary/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                    <p v-if="form.errors.visitor_name" class="mt-1 text-xs text-red-600">{{ form.errors.visitor_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">البريد الإلكتروني (اختياري)</label>
                    <input
                        v-model="form.visitor_email"
                        type="email"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-right text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-primary/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                    <p v-if="form.errors.visitor_email" class="mt-1 text-xs text-red-600">{{ form.errors.visitor_email }}</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">نوع الملاحظة</label>
                <select
                    v-model="form.feedback_type"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-right text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-primary/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                >
                    <option value="suggestion">اقتراح</option>
                    <option value="question">سؤال</option>
                    <option value="problem">الإبلاغ عن مشكلة</option>
                    <option value="like">إعجاب بالمشروع</option>
                    <option value="general">ملاحظة عامة</option>
                </select>
                <p v-if="form.errors.feedback_type" class="mt-1 text-xs text-red-600">{{ form.errors.feedback_type }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">عنوان الملاحظة</label>
                <input
                    v-model="form.title"
                    type="text"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-right text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-primary/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    required
                />
                <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">نص الملاحظة</label>
                <textarea
                    v-model="form.message"
                    rows="5"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-right text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-primary/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    required
                ></textarea>
                <p v-if="form.errors.message" class="mt-1 text-xs text-red-600">{{ form.errors.message }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">التقييم</label>
                <div class="mt-2 flex items-center gap-2">
                    <button
                        v-for="value in ratingOptions"
                        :key="value"
                        type="button"
                        @click="form.rating = value"
                        :class="[
                            'rounded-full px-3 py-1 text-sm font-medium transition',
                            form.rating === value ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                        ]"
                    >
                        {{ value }} ★
                    </button>
                </div>
                <p v-if="form.errors.rating" class="mt-1 text-xs text-red-600">{{ form.errors.rating }}</p>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    @click="props.onClose"
                >
                    إلغاء
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark disabled:opacity-50"
                >
                    إرسال
                </button>
            </div>
        </form>
    </Modal>
</template>
