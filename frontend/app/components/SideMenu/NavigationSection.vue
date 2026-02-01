<template>
  <div class="py-2">
    <!-- 設定リンク -->
    <NuxtLink
      to="/settings"
      class="flex items-center gap-3 px-5 py-4 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
      @click="handleNavigate"
    >
      <UIcon name="i-lucide-settings" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
      <span class="text-base">設定</span>
    </NuxtLink>

    <!-- 区切り線 -->
    <div class="mx-5 border-t border-gray-200 dark:border-gray-700" />

    <!-- 問題/ご意見リンク -->
    <NuxtLink
      to="/feedback"
      class="flex items-center gap-3 px-5 py-4 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
      @click="handleNavigate"
    >
      <UIcon name="i-lucide-message-square" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
      <span class="text-base">問題/ご意見</span>
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
const emit = defineEmits<{
  navigate: [];
}>();

/**
 * ナビゲーション時に親コンポーネントに通知
 */
const handleNavigate = () => {
  emit('navigate');
};
</script>
