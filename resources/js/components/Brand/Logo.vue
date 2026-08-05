<script setup lang="ts">
defineProps<{
    size?: 'sm' | 'md' | 'lg';
}>();
</script>

<template>
    <div class="flex items-center gap-3" :class="size === 'sm' ? 'gap-2' : size === 'lg' ? 'gap-4' : 'gap-3'">
        <!-- The user should place the CET logo at this path or update the src -->
        <img
            src="/images/logo.png"
            alt="شعار كلية التقنية الإلكترونية"
            class="flex-shrink-0 object-contain"
            :class="{
                'h-8 w-8': size === 'sm',
                'h-14 w-14': size === 'md' || !size,
                'h-16 w-16': size === 'lg',
            }"
        />
        <div class="flex flex-col leading-tight">
            <span
                class="font-display font-bold text-primary-dark"
                :class="{
                    'text-[11px] whitespace-nowrap': size === 'sm',
                    'text-base': size === 'md' || !size,
                    'text-xl': size === 'lg',
                }"
                >كلية التقنية الإلكترونية</span
            >
            <span
                v-if="size !== 'sm'"
                class="font-body text-text-muted"
                :class="{
                    'text-xs': size === 'md' || !size,
                    'text-sm': size === 'lg',
                }"
                >نظام أرشفة مشاريع التخرج</span
            >
        </div>
    </div>
</template>
