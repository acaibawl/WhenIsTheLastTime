<template>
  <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
    <div class="flex-1">
      <div class="text-base font-medium text-gray-900 dark:text-white">
        {{ label }}
      </div>
      <div
        v-if="description"
        class="text-sm text-gray-500 dark:text-gray-400 mt-1"
      >
        {{ description }}
      </div>
    </div>
    <div class="ml-3 flex-shrink-0">
      <USwitch
        :model-value="modelValue"
        @update:model-value="$emit('update:modelValue', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  label: string;
  description?: string;
  modelValue: boolean;
}>();

defineEmits<{
  'update:modelValue': [value: boolean];
}>();
</script>
