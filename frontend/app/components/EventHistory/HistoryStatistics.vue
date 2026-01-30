<template>
  <div class="bg-white dark:bg-gray-800 py-5 px-5 border-b border-gray-200 dark:border-gray-700">
    <div class="space-y-2">
      <!-- 平均間隔 -->
      <div class="text-base text-gray-900 dark:text-gray-100">
        <span class="font-bold">{{ statistics.averageInterval }}</span>
      </div>

      <!-- 平均日数 -->
      <div class="text-base text-gray-900 dark:text-gray-100">
        <span class="font-bold">{{ statistics.averageDays }}日</span>
        <span class="text-gray-600 dark:text-gray-400">平均</span>
      </div>

      <!-- 合計 -->
      <div class="text-base text-gray-900 dark:text-gray-100">
        <span class="font-bold">{{ statistics.total }}</span>
        <span class="text-gray-600 dark:text-gray-400">合計</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Statistics {
  thisWeek: number;
  thisMonth: number;
  total: number;
  averageInterval: string;
  averageDays: number;
}

defineProps<{
  statistics: Statistics;
}>();
</script>
