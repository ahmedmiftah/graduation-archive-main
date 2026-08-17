<script setup lang="ts">
import { CheckCircle2, CircleDot, Clock } from 'lucide-vue-next';

interface Stage {
    key: string;
    name_ar: string;
    sort_order: number;
    status: 'completed' | 'current' | 'upcoming';
    needs_student_action: boolean;
}

defineProps<{ stages: Stage[] }>();
</script>

<template>
    <ol class="space-y-0">
        <li v-for="(stage, idx) in stages" :key="stage.key" class="relative flex gap-3 pb-6 last:pb-0">
            <span v-if="idx < stages.length - 1" class="absolute right-[11px] top-6 h-full w-0.5" :class="stage.status === 'completed' ? 'bg-green-400' : 'bg-gray-200 dark:bg-gray-700'" />

            <span
                class="z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-green-500 text-white': stage.status === 'completed',
                    'bg-blue-600 text-white ring-4 ring-blue-100 dark:ring-blue-900/40': stage.status === 'current',
                    'bg-gray-200 text-gray-400 dark:bg-gray-700': stage.status === 'upcoming',
                }"
            >
                <CheckCircle2 v-if="stage.status === 'completed'" class="h-4 w-4" />
                <CircleDot v-else-if="stage.status === 'current'" class="h-3.5 w-3.5" />
                <span v-else class="h-1.5 w-1.5 rounded-full bg-current" />
            </span>

            <div class="flex-1 pt-0.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="text-sm font-medium"
                        :class="{
                            'text-green-700 dark:text-green-400': stage.status === 'completed',
                            'text-blue-700 dark:text-blue-400': stage.status === 'current',
                            'text-gray-400 dark:text-gray-500': stage.status === 'upcoming',
                        }"
                    >
                        {{ stage.name_ar }}
                    </span>
                    <span
                        v-if="stage.needs_student_action"
                        class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-400"
                    >
                        <Clock class="h-3 w-3" />
                        يتطلب إجراءً منك
                    </span>
                </div>
            </div>
        </li>
    </ol>
</template>
