<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import { type Department } from '@/types';
import { useForm } from '@inertiajs/vue3';

interface AvailableFacultyMember {
    id: number;
    full_name: string;
    degree?: { degree_code: string } | null;
    departments?: Department[];
}

const props = defineProps<{
    show: boolean;
    projectId: number;
    availableExaminers: AvailableFacultyMember[];
}>();

const emit = defineEmits<{
    assigned: [];
    cancelled: [];
}>();

const form = useForm({
    faculty_member_id: '' as number | '',
});

function submit() {
    form.post(route('projects.assign-faculty-member', [props.projectId]), {
        onSuccess: () => {
            form.reset();
            emit('assigned');
        },
    });
}

function cancel() {
    form.reset();
    emit('cancelled');
}
</script>

<template>
    <Modal :show="show" title="تعيين عضو هيئة تدريس" @close="cancel">
        <form id="assign-faculty-member-form" class="space-y-4" @submit.prevent="submit">
            <div v-if="availableExaminers.length === 0">
                <p class="text-sm text-gray-500">لا يوجد أعضاء هيئة تدريس متاحون للتعيين</p>
            </div>
            <div v-else>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">اختر عضو هيئة التدريس</label>
                <select
                    v-model="form.faculty_member_id"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    :class="{ 'border-red-500': form.errors.faculty_member_id }"
                >
                    <option value="">-- اختر عضواً --</option>
                    <option v-for="member in availableExaminers" :key="member.id" :value="member.id">
                        {{ member.full_name }}{{ member.degree ? ' — ' + member.degree.degree_code : ''
                        }}{{ member.departments?.length ? ' (' + member.departments.map((d) => d.name).join('، ') + ')' : '' }}
                    </option>
                </select>
                <p v-if="form.errors.faculty_member_id" class="mt-1 text-xs text-red-600">{{ form.errors.faculty_member_id }}</p>
            </div>
        </form>
        <template #footer>
            <button
                type="button"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                @click="cancel"
            >
                إلغاء
            </button>
            <button
                v-if="availableExaminers.length > 0"
                type="submit"
                form="assign-faculty-member-form"
                :disabled="form.processing || !form.faculty_member_id"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
            >
                تعيين
            </button>
        </template>
    </Modal>
</template>
