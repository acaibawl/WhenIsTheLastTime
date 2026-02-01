<template>
  <component
    :is="clickable ? 'button' : 'div'"
    :class="[
      'w-full flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0',
      clickable ? 'hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer' : '',
    ]"
    :type="clickable ? 'button' : undefined"
    @click="clickable ? $emit('click') : undefined"
  >
    <div class="flex-1 text-left">
      <div class="text-base font-medium text-gray-900 dark:text-white">
        {{ label }}
      </div>
      <div
        v-if="description || value"
        class="text-sm text-gray-500 dark:text-gray-400 mt-1"
      >
        {{ description || value }}
      </div>
    </div>
    <div v-if="clickable" class="ml-3 flex-shrink-0">
      <UIcon name="i-lucide-chevron-right" class="w-5 h-5 text-gray-400" />
    </div>
  </component>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string;
    value?: string;
    description?: string;
    clickable?: boolean;
  }>(),
  {
    value: undefined,
    description: undefined,
    clickable: true,
  },
);

defineEmits<{
  click: [];
}>();
</script>
