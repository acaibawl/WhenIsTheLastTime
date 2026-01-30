<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- ヘッダー -->
    <header class="sticky top-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="container mx-auto px-4 py-3 flex items-center justify-between">
        <!-- 戻るボタン -->
        <button
          class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          aria-label="戻る"
          @click="navigateBack"
        >
          <UIcon name="i-lucide-arrow-left" class="w-6 h-6" />
        </button>

        <!-- イベント情報 -->
        <div v-if="event" class="flex items-center gap-2 flex-1 justify-center">
          <span class="text-2xl">{{ getCategoryIcon(event.categoryIcon) }}</span>
          <h1 class="text-lg font-semibold text-gray-900 dark:text-white truncate max-w-50">
            {{ event.name }}
          </h1>
        </div>
        <div v-else class="flex-1" />

        <!-- メニューボタン -->
        <UDropdown :items="menuItems">
          <UButton
            icon="i-lucide-more-vertical"
            color="neutral"
            variant="ghost"
            aria-label="メニューを開く"
          />
        </UDropdown>
      </div>
    </header>

    <!-- ローディング状態 -->
    <div v-if="isLoading" class="flex justify-center items-center py-12">
      <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-blue-500" />
    </div>

    <!-- エラー状態 -->
    <div v-else-if="error" class="text-center py-12 px-4">
      <UIcon name="i-lucide-alert-circle" class="w-12 h-12 mx-auto text-red-500 mb-4" />
      <p class="text-gray-600 dark:text-gray-400 mb-4">
        {{ error }}
      </p>
      <UButton @click="loadData">再読み込み</UButton>
    </div>

    <!-- メインコンテンツ -->
    <div v-else class="pb-24">
      <!-- 統計バッジ -->
      <StatisticsBadges :statistics="statistics" />

      <!-- 履歴統計 -->
      <HistoryStatistics :statistics="statistics" />

      <!-- 履歴リスト -->
      <HistoryList
        v-if="groupedHistories.length > 0"
        :grouped-histories="groupedHistories"
        :format-elapsed-time="formatElapsedTime"
        @select="handleSelectHistory"
      />

      <!-- 空の状態 -->
      <div v-else class="text-center py-12 px-4">
        <UIcon name="i-lucide-file-text" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
          履歴がありません
        </h2>
        <p class="text-gray-500 dark:text-gray-400">
          「+ 履歴を追加」ボタンから最初の履歴を追加しましょう
        </p>
      </div>
    </div>

    <!-- FAB（履歴追加ボタン） -->
    <button
      class="fixed bottom-6 right-6 bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-full shadow-lg hover:shadow-xl transition-all flex items-center gap-2"
      aria-label="履歴を追加"
      @click="handleAddHistory"
    >
      <UIcon name="i-lucide-plus" class="w-5 h-5" />
      <span class="font-medium">履歴を追加</span>
    </button>

    <!-- 削除確認ダイアログ -->
    <UModal v-model="showDeleteDialog">
      <UCard>
        <template #header>
          <h2 class="text-lg font-semibold">
            このイベントを削除しますか？
          </h2>
        </template>

        <div class="space-y-4">
          <p class="text-gray-600 dark:text-gray-400">
            「<span class="font-semibold">{{ event?.name }}</span>」
          </p>
          <p class="text-gray-600 dark:text-gray-400">
            およびすべての履歴が削除されます。<br>
            この操作は取り消せません。
          </p>
        </div>

        <template #footer>
          <div class="flex gap-3 justify-end">
            <UButton
              color="neutral"
              variant="ghost"
              @click="showDeleteDialog = false"
            >
              キャンセル
            </UButton>
            <UButton
              color="error"
              @click="handleDeleteEvent"
            >
              削除
            </UButton>
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { getCategoryIcon } from '~/constants/categories';
import StatisticsBadges from '~/components/EventHistory/StatisticsBadges.vue';
import HistoryStatistics from '~/components/EventHistory/HistoryStatistics.vue';
import HistoryList from '~/components/EventHistory/HistoryList.vue';

// ページメタデータ（認証ミドルウェアを適用）
definePageMeta({
  middleware: 'auth',
});

const route = useRoute();
const router = useRouter();
const eventId = route.params.id as string;

const {
  event,
  histories,
  isLoading,
  error,
  statistics,
  groupedHistories,
  showDeleteDialog,
  loadData,
  deleteEvent,
  formatElapsedTime,
} = useEventHistory(eventId);

// メニュー項目
const menuItems = computed(() => [
  [{
    label: '編集',
    icon: 'i-lucide-pencil',
    click: () => router.push(`/events/${eventId}/edit`),
  }],
  [{
    label: '削除',
    icon: 'i-lucide-trash',
    click: () => showDeleteDialog.value = true,
  }],
]);

// 戻るボタン
const navigateBack = () => {
  router.push('/');
};

// 履歴追加
const handleAddHistory = () => {
  // TODO: 履歴追加画面へ遷移（未実装）
  const toast = useToast();
  toast.add({
    title: '履歴追加機能は未実装です',
    color: 'warning',
  });
};

// 履歴選択（編集）
const handleSelectHistory = (historyId: number) => {
  // TODO: 履歴編集画面へ遷移（未実装）
  const toast = useToast();
  toast.add({
    title: '履歴編集機能は未実装です',
    color: 'warning',
  });
};

// イベント削除
const handleDeleteEvent = async () => {
  await deleteEvent();
};

// 初回ロード
onMounted(() => {
  loadData();
});
</script>
