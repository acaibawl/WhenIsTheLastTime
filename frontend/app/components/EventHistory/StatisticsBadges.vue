<template>
  <div class="bg-gray-50 dark:bg-gray-800 py-4 px-5 border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-around gap-4">
      <!-- 今週 -->
      <div class="text-center">
        <div class="text-2xl font-bold text-blue-500 dark:text-blue-400">
          {{ statistics.thisWeek }}
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
          今週
        </div>
      </div>

      <!-- 区切り -->
      <div class="text-gray-300 dark:text-gray-600">
        ｜
      </div>

      <!-- 今月 -->
      <div class="text-center">
        <div class="text-2xl font-bold text-blue-500 dark:text-blue-400">
          {{ statistics.thisMonth }}
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
          今月
        </div>
      </div>

      <!-- 区切り -->
      <div class="text-gray-300 dark:text-gray-600">
        ｜
      </div>

      <!-- 合計 -->
      <div class="text-center">
        <div class="text-2xl font-bold text-blue-500 dark:text-blue-400">
          {{ statistics.total }}
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
          合計
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">

import type { Statistics } from '~~/app/types/eventHistory';

defineProps<{
  statistics: Statistics;
}>();
</script>
