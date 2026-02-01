<template>
  <div class="py-4">
    <!-- セクションタイトル -->
    <h3 class="px-5 pb-3 text-sm font-semibold text-gray-500 dark:text-gray-400">
      カテゴリーでフィルター
    </h3>

    <!-- アイコングリッド -->
    <div
      role="group"
      aria-label="カテゴリーでフィルター"
      class="px-5 grid grid-cols-5 gap-2"
    >
      <button
        v-for="category in CATEGORY_ICONS"
        :key="category.id"
        type="button"
        role="checkbox"
        :aria-checked="isSelected(category.id)"
        :aria-label="`${category.label}カテゴリー`"
        :class="[
          'w-12 h-12 flex items-center justify-center rounded-xl text-2xl transition-all duration-150',
          isSelected(category.id)
            ? 'bg-blue-50 dark:bg-blue-900/30 border-2 border-blue-500 shadow-sm'
            : 'bg-gray-100 dark:bg-gray-700 border-2 border-transparent hover:bg-gray-200 dark:hover:bg-gray-600',
        ]"
        @click="toggleCategory(category.id)"
      >
        {{ category.icon }}
      </button>
    </div>

    <!-- 選択中のカテゴリー数 -->
    <div
      v-if="eventsStore.selectedCategories.length > 0"
      class="px-5 pt-3"
    >
      <span class="text-sm text-blue-600 dark:text-blue-400">
        {{ eventsStore.selectedCategories.length }}個選択中
      </span>
      <button
        type="button"
        class="ml-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 underline"
        @click="clearCategories"
      >
        クリア
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CategoryType } from '~/constants/categories';
import { CATEGORY_ICONS } from '~/constants/categories';
import { useEventsStore } from '~/stores/events';

const eventsStore = useEventsStore();

/**
 * カテゴリーが選択されているかチェック
 */
const isSelected = (categoryId: CategoryType): boolean => {
  return eventsStore.selectedCategories.includes(categoryId);
};

/**
 * カテゴリーをトグル
 */
const toggleCategory = (categoryId: CategoryType) => {
  eventsStore.toggleCategory(categoryId);
};

/**
 * カテゴリー選択をクリア
 */
const clearCategories = () => {
  eventsStore.selectedCategories = [];
};
</script>
