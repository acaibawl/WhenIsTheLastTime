<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- ヘッダー -->
    <header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="container mx-auto px-4 py-3 flex items-center justify-between">
        <!-- 戻るボタン -->
        <button
          class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          aria-label="戻る"
          @click="navigateBack"
        >
          <UIcon name="i-lucide-arrow-left" class="w-6 h-6" />
        </button>

        <!-- タイトル -->
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white flex-1 text-center">
          時間設定
        </h1>

        <!-- 右側のスペーサー -->
        <div class="w-10" />
      </div>
    </header>

    <main class="container mx-auto max-w-3xl">
      <div class="bg-white dark:bg-gray-800 mt-4 pl-5 rounded-lg overflow-hidden">
        <URadioGroup
          v-model="selectedTimeOrigin"
          :items="timeOriginOptions"
          :ui="{ item: 'items-center' }"
          class="divide-y divide-gray-200 dark:divide-gray-700"
        >
          <template #label="{ item }">
            <div class="px-5 py-4">
              <div class="text-base font-medium text-gray-900 dark:text-white">
                {{ item.label }}
              </div>
              <div
                v-if="item.descriptionLabel"
                class="text-sm text-gray-500 dark:text-gray-400 mt-1"
              >
                {{ item.descriptionLabel }}
              </div>
            </div>
          </template>
        </URadioGroup>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useSettingsStore, type TimeOrigin } from '~/stores/settings';

const router = useRouter();
const settingsStore = useSettingsStore();

const timeOriginOptions = [
  {
    value: 'midnight',
    label: '真夜中を起点とする',
    descriptionLabel: '午前0時に日付が変わる',
  },
  {
    value: '24hours',
    label: '24時間ごと（準備中）',
    descriptionLabel: '記録時刻から24時間後',
    disabled: true,
  },
];

const selectedTimeOrigin = ref<TimeOrigin>(settingsStore.localSettings.display.timeOrigin);

// 選択が変更されたら保存して戻る
watch(selectedTimeOrigin, (newValue) => {
  settingsStore.updateTimeOrigin(newValue);
  router.back();
});

const navigateBack = () => {
  router.back();
};

// 初期化
onMounted(() => {
  settingsStore.loadLocalSettings();
  selectedTimeOrigin.value = settingsStore.localSettings.display.timeOrigin;
});
</script>
