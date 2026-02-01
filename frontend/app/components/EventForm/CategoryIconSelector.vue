<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
      <span class="required">カテゴリー</span>
    </label>
    <div
      role="radiogroup"
      aria-label="カテゴリーアイコン選択"
      class="grid grid-cols-5 gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl"
    >
      <button
        v-for="icon in CATEGORY_ICONS"
        :key="icon.id"
        type="button"
        role="radio"
        :aria-checked="modelValue === icon.id"
        :aria-label="`${icon.label}カテゴリー`"
        :class="[
          'flex items-center justify-center w-12 h-12 md:w-14 md:h-14 rounded-xl text-2xl md:text-3xl transition-all duration-150',
          'hover:bg-gray-200 dark:hover:bg-gray-700',
          modelValue === icon.id
            ? 'bg-blue-100 dark:bg-blue-900 border-2 border-blue-500 opacity-100 shadow-md'
            : 'bg-gray-100 dark:bg-gray-700 border-2 border-transparent opacity-60',
        ]"
        @click="selectIcon(icon.id)"
        @keydown.space.prevent="selectIcon(icon.id)"
        @keydown.enter.prevent="selectIcon(icon.id)"
      >
        {{ icon.icon }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CategoryType } from '~/constants/categories';
import { CATEGORY_ICONS } from '~/constants/categories';

// Props & Emits
interface Props {
  modelValue: CategoryType;
}

interface Emits {
  (e: 'update:modelValue', value: CategoryType): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

// メソッド
const selectIcon = (iconId: CategoryType) => {
  emit('update:modelValue', iconId);
};
</script>
