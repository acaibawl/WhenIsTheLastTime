<template>
  <div class="space-y-6 py-4">
    <div
      v-for="group in groupedHistories"
      :key="group.yearMonth"
    >
      <!-- 年月グループヘッダー -->
      <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800">
        <h2 class="text-sm font-semibold text-gray-600 dark:text-gray-400">
          {{ group.yearMonth }}
        </h2>
      </div>

      <!-- 履歴エントリ -->
      <div class="space-y-2 px-4 pt-2">
        <div
          v-for="history in group.histories"
          :key="history.id"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm hover:shadow-md transition-all cursor-pointer active:scale-[0.98]"
          @click="$emit('select', history.id)"
        >
          <div class="flex items-start gap-4">
            <!-- 日付バッジ -->
            <div class="shrink-0">
              <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/30 border-2 border-blue-500 dark:border-blue-400 flex items-center justify-center">
                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                  {{ formatDay(history.executedAt) }}
                </span>
              </div>
            </div>

            <!-- メモとサブ情報 -->
            <div class="flex-1 min-w-0">
              <!-- メモ -->
              <div class="text-base font-medium text-gray-900 dark:text-white mb-1 line-clamp-2">
                {{ history.memo || '（メモなし）' }}
              </div>

              <!-- サブ情報 -->
              <div class="text-sm text-gray-600 dark:text-gray-400">
                <span>{{ formatWeekday(history.executedAt) }}</span>
                <span class="mx-2">{{ formatTime(history.executedAt) }}</span>
                <span>{{ formatElapsedTime(history.executedAt) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { format } from 'date-fns';
import { ja } from 'date-fns/locale';

import type { GroupedHistory } from '~~/app/types/eventHistory';

defineProps<{
  groupedHistories: GroupedHistory[];
  formatElapsedTime: (executedAt: string) => string;
}>();

defineEmits<{
  select: [historyId: number];
}>();

// 日付（日）をフォーマット
const formatDay = (dateStr: string): string => {
  return format(new Date(dateStr), 'dd');
};

// 曜日をフォーマット
const formatWeekday = (dateStr: string): string => {
  return format(new Date(dateStr), 'E', { locale: ja });
};

// 時刻をフォーマット
const formatTime = (dateStr: string): string => {
  return format(new Date(dateStr), 'HH:mm');
};
</script>
