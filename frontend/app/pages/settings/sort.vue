<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- ヘッダー -->
    <header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="container mx-auto px-4 py-3 flex items-center justify-between">
        <!-- 戻るボタン -->
        <NuxtLink
          to="/settings"
          class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          aria-label="戻る"
        >
          <UIcon name="i-lucide-arrow-left" class="w-6 h-6" />
        </NuxtLink>

        <!-- タイトル -->
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white flex-1 text-center">
          ソート順
        </h1>

        <!-- 右側のスペーサー -->
        <div class="w-10"></div>
      </div>
    </header>

    <main class="container mx-auto max-w-3xl">
      <div class="bg-white dark:bg-gray-800 pl-5 mt-4 rounded-lg overflow-hidden">
        <URadioGroup
          v-model="selectedSort"
          :items="sortOptions"
          :ui="{ item: 'items-center' }"
          class="divide-y divide-gray-200 dark:divide-gray-700"
        >
          <template #label="{ item }">
            <div class="px-5 py-4">
              <span class="text-base font-medium text-gray-900 dark:text-white">
                {{ item.label }}
              </span>
            </div>
          </template>
        </URadioGroup>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useSettingsStore, type SortOrder } from '~/stores/settings';

const settingsStore = useSettingsStore();

const sortOptions = [
  { value: 'alphabetical', label: 'アルファベット順' },
  { value: 'date_desc', label: '日時降順（最新が上）' },
  { value: 'date_asc', label: '日時昇順（古いものが上）' },
];

const selectedSort = ref<SortOrder>(settingsStore.localSettings.display.sortOrder);

// 選択が変更されたら保存して戻る
watch(selectedSort, (newValue) => {
  settingsStore.updateSortOrder(newValue);
});

// 初期化
onMounted(() => {
  settingsStore.loadLocalSettings();
  selectedSort.value = settingsStore.localSettings.display.sortOrder;
});
</script>
