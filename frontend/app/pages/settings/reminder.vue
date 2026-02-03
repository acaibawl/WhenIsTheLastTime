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
          リマインダー
        </h1>

        <!-- 右側のスペーサー -->
        <div class="w-10" />
      </div>
    </header>

    <main class="container mx-auto max-w-3xl py-4">
      <!-- 準備中のお知らせ -->
      <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4 mx-4">
        <div class="flex items-center gap-2">
          <UIcon name="i-lucide-info" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
          <span class="text-sm text-yellow-700 dark:text-yellow-300">
            この機能は準備中です
          </span>
        </div>
      </div>

      <!-- リマインダーを有効にする -->
      <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden mx-4 mb-4">
        <div class="flex items-center justify-between px-5 py-4">
          <div class="flex-1">
            <div class="text-base font-medium text-gray-900 dark:text-white">
              リマインダーを有効にする
            </div>
          </div>
          <div class="ml-3 flex-shrink-0">
            <USwitch
              v-model="reminderEnabled"
              disabled
            />
          </div>
        </div>
      </div>

      <!-- 通知タイミング設定 -->
      <div
        :class="[
          'bg-white dark:bg-gray-800 rounded-lg overflow-hidden mx-4 mb-4',
          !reminderEnabled ? 'opacity-50 pointer-events-none' : '',
        ]"
      >
        <h2 class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900">
          通知タイミング
        </h2>

        <!-- 通知頻度 -->
        <button
          class="w-full flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          disabled
        >
          <div class="flex-1 text-left">
            <div class="text-base font-medium text-gray-900 dark:text-white">
              通知頻度
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              {{ timingTypeLabel }}
            </div>
          </div>
          <UIcon name="i-lucide-chevron-right" class="w-5 h-5 text-gray-400" />
        </button>

        <!-- 通知時刻 -->
        <button
          class="w-full flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          disabled
        >
          <div class="flex-1 text-left">
            <div class="text-base font-medium text-gray-900 dark:text-white">
              通知時刻
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              {{ reminderTime }}
            </div>
          </div>
          <UIcon name="i-lucide-chevron-right" class="w-5 h-5 text-gray-400" />
        </button>

        <!-- 通知対象イベント -->
        <button
          class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          disabled
        >
          <div class="flex-1 text-left">
            <div class="text-base font-medium text-gray-900 dark:text-white">
              通知対象イベント
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              {{ targetEventsLabel }}
            </div>
          </div>
          <UIcon name="i-lucide-chevron-right" class="w-5 h-5 text-gray-400" />
        </button>
      </div>

      <!-- プレビュー -->
      <div
        :class="[
          'bg-white dark:bg-gray-800 rounded-lg overflow-hidden mx-4',
          !reminderEnabled ? 'opacity-50' : '',
        ]"
      >
        <h2 class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900">
          プレビュー
        </h2>
        <div class="px-5 py-4">
          <div class="flex items-start gap-3 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
            <span class="text-xl">📱</span>
            <div>
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                エアコンフィルター掃除
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                最後から2年3ヶ月経過しています
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useSettingsStore } from '~/stores/settings';

const router = useRouter();
const settingsStore = useSettingsStore();

// State
const reminderEnabled = ref(false);
const reminderTime = ref('09:00');

// Computed
const timingTypeLabel = computed(() => {
  const labels: Record<string, string> = {
    daily: '毎日',
    weekly: '週1回',
    monthly: '月1回',
  };
  return labels[settingsStore.serverSettings.notification.reminder.timing.type] || '毎日';
});

const targetEventsLabel = computed(() => {
  const labels: Record<string, string> = {
    all: 'すべてのイベント',
    week: '1週間以上前のイベント',
    month: '1ヶ月以上前のイベント',
    year: '1年以上前のイベント',
  };
  return labels[settingsStore.serverSettings.notification.reminder.targetEvents] || '1週間以上前のイベント';
});

const navigateBack = () => {
  router.back();
};

// 初期化
onMounted(async () => {
  await settingsStore.loadServerSettings();
  reminderEnabled.value = settingsStore.serverSettings.notification.reminder.enabled;
  reminderTime.value = settingsStore.serverSettings.notification.reminder.timing.time;
});
</script>
