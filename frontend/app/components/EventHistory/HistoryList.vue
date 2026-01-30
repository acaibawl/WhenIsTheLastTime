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
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm hover:shadow-md transition-all"
        >
          <div class="flex items-start gap-4">
            <!-- 日付バッジ -->
            <div
              class="shrink-0 cursor-pointer active:scale-[0.98]"
              @click="$emit('select', history.id)"
            >
              <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/30 border-2 border-blue-500 dark:border-blue-400 flex items-center justify-center">
                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                  {{ formatDay(history.executedAt) }}
                </span>
              </div>
            </div>

            <!-- メモとサブ情報 -->
            <div
              class="flex-1 min-w-0 cursor-pointer active:scale-[0.98]"
              @click="$emit('select', history.id)"
            >
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

            <!-- メニューボタン -->
            <div class="shrink-0">
              <UDropdownMenu :items="getMenuItems(history)" :ui="{ content: 'w-32' }">
                <UButton
                  icon="i-lucide-more-vertical"
                  color="neutral"
                  variant="ghost"
                  size="sm"
                  aria-label="履歴メニューを開く"
                  @click.stop
                />
              </UDropdownMenu>
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

import type { GroupedHistory, History } from '~~/app/types/eventHistory';

const props = defineProps<{
  groupedHistories: GroupedHistory[];
  formatElapsedTime: (executedAt: string) => string;
  canDeleteHistory: boolean;
}>();

const emit = defineEmits<{
  select: [historyId: number];
  delete: [history: History];
}>();

// メニュー項目を生成
const getMenuItems = (history: History) => {
  const items = [
    [
      {
        label: '編集',
        icon: 'i-lucide-pencil',
        onSelect: () => emit('select', history.id),
      },
    ],
  ];

  // 履歴が2件以上の場合のみ削除ボタンを表示
  if (props.canDeleteHistory) {
    items.push([
      {
        label: '削除',
        icon: 'i-lucide-trash',
        color: 'error' as const,
        onSelect: () => emit('delete', history),
      },
    ]);
  }

  return items;
};

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
