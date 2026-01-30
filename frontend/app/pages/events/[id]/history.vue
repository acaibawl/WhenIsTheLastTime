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
        <UDropdownMenu :items="menuItems" :ui="{ content: 'w-40' }">
          <UButton
            icon="i-lucide-more-vertical"
            color="neutral"
            variant="ghost"
            aria-label="メニューを開く"
          />
        </UDropdownMenu>
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
        :can-delete-history="canDeleteHistory"
        @select="handleSelectHistory"
        @delete="handleDeleteHistoryClick"
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

    <!-- イベント削除確認ダイアログ -->
    <UModal
      v-model:open="showDeleteDialog"
      title="このイベントを削除しますか？"
      :ui="{ footer: 'justify-end' }"
    >
      <template #body>
        <div class="space-y-4">
          <p class="text-gray-600 dark:text-gray-400">
            「<span class="font-semibold">{{ event?.name }}</span>」
          </p>
          <p class="text-gray-600 dark:text-gray-400">
            およびすべての履歴が削除されます。<br>
            この操作は取り消せません。
          </p>
        </div>
      </template>

      <template #footer>
        <UButton
          color="neutral"
          variant="outline"
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
      </template>
    </UModal>

    <!-- 履歴削除確認ダイアログ -->
    <UModal
      v-model:open="showHistoryDeleteDialog"
      title="この履歴を削除しますか？"
      :ui="{ footer: 'justify-end' }"
    >
      <template #body>
        <div class="space-y-2">
          <p class="text-gray-600 dark:text-gray-400">
            {{ historyToDelete ? formatDateTime(historyToDelete.executedAt) : '' }}
          </p>
          <p class="text-gray-600 dark:text-gray-400">
            {{ historyToDelete?.memo || '（メモなし）' }}
          </p>
          <p class="text-sm text-gray-500 dark:text-gray-500 mt-4">
            この操作は取り消せません。
          </p>
        </div>
      </template>

      <template #footer>
        <UButton
          color="neutral"
          variant="outline"
          @click="showHistoryDeleteDialog = false"
        >
          キャンセル
        </UButton>
        <UButton
          color="error"
          @click="handleDeleteHistory"
        >
          削除
        </UButton>
      </template>
    </UModal>

    <!-- 履歴追加モーダル -->
    <UModal
      v-model:open="showAddHistoryModal"
      title="履歴を追加"
      :ui="{ footer: 'justify-end' }"
    >
      <template #body>
        <div class="space-y-4">
          <!-- 日付入力 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              日付 <span class="text-red-500">*</span>
            </label>
            <input
              v-model="newHistoryDate"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            >
          </div>

          <!-- 時刻入力 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              時刻 <span class="text-red-500">*</span>
            </label>
            <input
              v-model="newHistoryTime"
              type="time"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            >
          </div>

          <!-- メモ入力 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              メモ
            </label>
            <textarea
              v-model="newHistoryMemo"
              rows="3"
              maxlength="500"
              placeholder="メモを入力（任意）"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
            />
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">
              {{ newHistoryMemo.length }} / 500
            </p>
          </div>
        </div>
      </template>

      <template #footer>
        <UButton
          color="neutral"
          variant="outline"
          :disabled="isAddingHistory"
          @click="showAddHistoryModal = false"
        >
          キャンセル
        </UButton>
        <UButton
          color="primary"
          :loading="isAddingHistory"
          :disabled="!newHistoryDate || !newHistoryTime"
          @click="handleSaveNewHistory"
        >
          追加
        </UButton>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { format } from 'date-fns';
import { ja } from 'date-fns/locale';
import { getCategoryIcon } from '~/constants/categories';
import StatisticsBadges from '~/components/EventHistory/StatisticsBadges.vue';
import HistoryStatistics from '~/components/EventHistory/HistoryStatistics.vue';
import HistoryList from '~/components/EventHistory/HistoryList.vue';
import type { History } from '~~/app/types/eventHistory';

const route = useRoute();
const router = useRouter();
const eventId = route.params.id as string;

const {
  event,
  isLoading,
  error,
  statistics,
  groupedHistories,
  showDeleteDialog,
  canDeleteHistory,
  loadData,
  deleteEvent,
  deleteHistory,
  addHistory,
  formatElapsedTime,
} = useEventHistory(eventId);

// 日時フォーマット用ヘルパー
const formatDateTime = (dateStr: string): string => {
  return format(new Date(dateStr), 'yyyy/MM/dd HH:mm', { locale: ja });
};

// 履歴削除用の状態
const showHistoryDeleteDialog = ref(false);
const historyToDelete = ref<History | null>(null);

// 履歴追加用の状態
const showAddHistoryModal = ref(false);
const newHistoryDate = ref('');
const newHistoryTime = ref('');
const newHistoryMemo = ref('');
const isAddingHistory = ref(false);

// 現在日時をデフォルト値として設定するヘルパー
const setDefaultDateTime = () => {
  const now = new Date();
  // YYYY-MM-DD形式（ローカルタイム）
  newHistoryDate.value = format(now, 'yyyy-MM-dd');
  // HH:MM形式（ローカルタイム）
  newHistoryTime.value = format(now, 'HH:mm');
  newHistoryMemo.value = '';
};

// メニュー項目
const menuItems = computed(() => [
  [
    {
      label: '編集',
      icon: 'i-lucide-pencil',
      onSelect: () => router.push(`/events/${eventId}/edit`),
    },
  ],
  [
    {
      label: '削除',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      onSelect: () => showDeleteDialog.value = true,
    },
  ],
]);

// 戻るボタン
const navigateBack = () => {
  router.push('/');
};

// 履歴追加
const handleAddHistory = () => {
  setDefaultDateTime();
  showAddHistoryModal.value = true;
};

// 履歴追加を保存
const handleSaveNewHistory = async () => {
  if (!newHistoryDate.value || !newHistoryTime.value) {
    const toast = useToast();
    toast.add({
      title: '日時を入力してください',
      color: 'warning',
    });
    return;
  }

  isAddingHistory.value = true;

  // 日付と時刻を結合してISO 8601形式に変換（ローカルタイムゾーン付き）
  const date = new Date(`${newHistoryDate.value}T${newHistoryTime.value}:00`);
  const executedAt = format(date, "yyyy-MM-dd'T'HH:mm:ssxxx");

  const success = await addHistory(executedAt, newHistoryMemo.value || undefined);

  isAddingHistory.value = false;

  if (success) {
    showAddHistoryModal.value = false;
    newHistoryDate.value = '';
    newHistoryTime.value = '';
    newHistoryMemo.value = '';
  }
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

// 履歴削除ボタンクリック
const handleDeleteHistoryClick = (history: History) => {
  historyToDelete.value = history;
  showHistoryDeleteDialog.value = true;
};

// 履歴削除実行
const handleDeleteHistory = async () => {
  if (!historyToDelete.value) return;

  const success = await deleteHistory(historyToDelete.value.id);
  if (success) {
    showHistoryDeleteDialog.value = false;
    historyToDelete.value = null;
  }
};

// イベント削除
const handleDeleteEvent = async () => {
  await deleteEvent();
};

// 初回ロード
await loadData();
</script>
