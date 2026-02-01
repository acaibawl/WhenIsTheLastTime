<template>
  <div class="py-4">
    <!-- セクションタイトル -->
    <h3 class="px-5 pb-3 text-sm font-semibold text-gray-500 dark:text-gray-400">
      時間でフィルター
    </h3>

    <!-- フィルターオプション -->
    <div role="radiogroup" aria-label="時間でフィルター">
      <button
        v-for="option in timeFilterOptions"
        :key="option.id"
        type="button"
        role="radio"
        :aria-checked="eventsStore.timeFilter === option.id"
        :aria-label="`${option.label}、${getCount(option.id)}件`"
        :class="[
          'w-full flex items-center justify-between px-5 py-3 transition-colors duration-150',
          eventsStore.timeFilter === option.id
            ? 'bg-blue-50 dark:bg-blue-900/20'
            : 'hover:bg-gray-50 dark:hover:bg-gray-700/50',
        ]"
        @click="selectFilter(option.id)"
      >
        <div class="flex items-center gap-3">
          <!-- ラジオボタン -->
          <div
            :class="[
              'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors',
              eventsStore.timeFilter === option.id
                ? 'border-blue-500 bg-blue-500'
                : 'border-gray-300 dark:border-gray-600',
            ]"
          >
            <div
              v-if="eventsStore.timeFilter === option.id"
              class="w-2 h-2 rounded-full bg-white"
            />
          </div>

          <!-- ラベル -->
          <span class="text-base text-gray-900 dark:text-white">
            {{ option.label }}
          </span>
        </div>

        <!-- 件数バッジ -->
        <span class="text-sm text-gray-500 dark:text-gray-400">
          ({{ getCount(option.id) }})
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { TimeFilterType } from '~/stores/events';
import { useEventsStore } from '~/stores/events';

interface TimeFilterOption {
  id: TimeFilterType;
  label: string;
}

const eventsStore = useEventsStore();

const timeFilterOptions: TimeFilterOption[] = [
  { id: 'all', label: '全て' },
  { id: 'weeks', label: '数週間前' },
  { id: 'months', label: '数ヶ月前' },
  { id: 'years', label: '数年前' },
];

/**
 * フィルター種別に対応するカウントを取得
 */
const getCount = (filterId: TimeFilterType): number => {
  return eventsStore.filterStats[filterId];
};

/**
 * フィルターを選択
 */
const selectFilter = (filterId: TimeFilterType) => {
  eventsStore.setTimeFilter(filterId);
};
</script>
