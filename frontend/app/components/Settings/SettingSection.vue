<template>
  <div class="py-2">
    <!-- セクションタイトル -->
    <h2 class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900">
      {{ title }}
    </h2>
    <!-- セクション内容 -->
    <div class="bg-white dark:bg-gray-800">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
}>();
</script>
