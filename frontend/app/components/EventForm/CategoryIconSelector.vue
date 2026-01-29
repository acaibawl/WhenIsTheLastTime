<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
      カテゴリー
      <span class="text-red-500">*</span>
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
// 型定義
type CategoryType
  = | 'pin'
    | 'book'
    | 'folder'
    | 'star'
    | 'chart'
    | 'sun'
    | 'person'
    | 'hospital'
    | 'medical'
    | 'leaf'
    | 'search'
    | 'people'
    | 'snowflake'
    | 'fire'
    | 'lightning';

interface CategoryIcon {
  id: CategoryType;
  icon: string;
  label: string;
  color: string;
}

// Props & Emits
interface Props {
  modelValue: CategoryType;
}

interface Emits {
  (e: 'update:modelValue', value: CategoryType): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

// カテゴリーアイコン定義
const CATEGORY_ICONS: CategoryIcon[] = [
  { id: 'pin', icon: '📌', label: 'ピン', color: '#EF4444' },
  { id: 'book', icon: '📚', label: '本', color: '#3B82F6' },
  { id: 'folder', icon: '📁', label: 'フォルダ', color: '#F59E0B' },
  { id: 'star', icon: '⭐', label: 'スター', color: '#EAB308' },
  { id: 'chart', icon: '📊', label: 'グラフ', color: '#8B5CF6' },
  { id: 'sun', icon: '☀️', label: '太陽', color: '#F97316' },
  { id: 'person', icon: '👤', label: '人物', color: '#6B7280' },
  { id: 'hospital', icon: '🏥', label: '病院', color: '#EC4899' },
  { id: 'medical', icon: '➕', label: 'プラス・医療', color: '#10B981' },
  { id: 'leaf', icon: '🍃', label: '葉', color: '#22C55E' },
  { id: 'search', icon: '🔍', label: '虫眼鏡', color: '#6366F1' },
  { id: 'people', icon: '👥', label: '複数人', color: '#8B5CF6' },
  { id: 'snowflake', icon: '❄️', label: '雪の結晶', color: '#06B6D4' },
  { id: 'fire', icon: '🔥', label: '炎', color: '#DC2626' },
  { id: 'lightning', icon: '⚡', label: '雷', color: '#EACC0B' },
];

// メソッド
const selectIcon = (iconId: CategoryType) => {
  emit('update:modelValue', iconId);
};
</script>
