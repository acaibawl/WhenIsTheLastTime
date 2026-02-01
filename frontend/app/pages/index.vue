<template>
  <div>
    <!-- サイドメニュー -->
    <SideMenu v-model="showSideMenu" :nickname="userNickname" />

    <!-- イベント作成モーダル（Teleportで body に配置） -->
    <Teleport to="body">
      <CreateEventModal v-if="showCreateModal" v-model="showCreateModal" @created="handleEventCreated" />
    </Teleport>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- ヘッダー -->
      <header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="container mx-auto px-4 py-3 flex items-center justify-between">
        <!-- ハンバーガーメニュー -->
        <button
          class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          aria-label="メニューを開く"
          @click="openSideMenu"
        >
          <UIcon name="i-lucide-menu" class="w-6 h-6" />
        </button>

        <!-- タイトル -->
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white flex-1 text-center">
          最後はいつ？
        </h1>

        <!-- ニックネーム -->
        <span class="text-sm text-gray-600 dark:text-gray-300 max-w-25 truncate">
          👤 {{ userNickname }}
        </span>
      </div>

      <!-- 検索バー -->
      <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-2">
        <UInput
          v-model="eventsStore.searchQuery"
          placeholder="イベントを検索..."
          icon="i-lucide-search"
          :trailing="true"
          size="lg"
          class="w-full"
        >
          <template #trailing>
            <UButton
              v-if="eventsStore.searchQuery"
              color="neutral"
              variant="link"
              icon="i-lucide-x"
              size="xs"
              @click="eventsStore.clearSearchQuery()"
            />
          </template>
        </UInput>
      </div>

      <!-- アクティブフィルター表示 -->
      <div
        v-if="hasActiveFilters"
        class="border-t border-gray-200 dark:border-gray-700 px-4 py-2 bg-blue-50 dark:bg-blue-900/20"
      >
        <div class="flex items-center justify-between">
          <span class="text-sm text-blue-700 dark:text-blue-300">
            フィルター適用中: {{ eventsStore.filteredEvents.length }}件
          </span>
          <button
            type="button"
            class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
            @click="eventsStore.clearFilters()"
          >
            クリア
          </button>
        </div>
      </div>
    </header>

    <!-- メインコンテンツ -->
    <main class="container mx-auto px-4 py-6 pb-24">
      <!-- ローディング状態 -->
      <div v-if="eventsStore.loading" class="flex justify-center items-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-blue-500" />
      </div>

      <!-- エラー状態 -->
      <div v-else-if="eventsStore.error" class="text-center py-12">
        <UIcon name="i-lucide-alert-circle" class="w-12 h-12 mx-auto text-red-500 mb-4" />
        <p class="text-gray-600 dark:text-gray-400 mb-4">
          {{ eventsStore.error }}
        </p>
        <UButton @click="eventsStore.fetchEvents()">再読み込み</UButton>
      </div>

      <!-- 空の状態 -->
      <div v-else-if="eventsStore.filteredEvents.length === 0" class="text-center py-12">
        <UIcon name="i-lucide-file-text" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
          {{ eventsStore.searchQuery || hasActiveFilters ? '該当するイベントがありません' : 'イベントがありません' }}
        </h2>
        <p class="text-gray-500 dark:text-gray-400 mb-6">
          {{ eventsStore.searchQuery || hasActiveFilters ? '別の条件で検索してみてください' : '「+」ボタンから最初のイベントを追加しましょう' }}
        </p>
      </div>

      <!-- イベント一覧 -->
      <div v-else class="space-y-3">
        <NuxtLink
          v-for="event in eventsStore.filteredEvents"
          :key="event.id"
          :to="`/events/${event.id}/history`"
          class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm hover:shadow-md transition-all active:scale-[0.98]"
        >
          <!-- イベント名 -->
          <div class="flex items-start gap-3 mb-2">
            <span class="text-2xl">{{ getCategoryIcon(event.categoryIcon) }}</span>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex-1 line-clamp-2">
              {{ event.name }}
            </h3>
          </div>

          <!-- 経過時間 -->
          <div :class="['text-2xl font-bold mb-2', getElapsedTimeColor(event.lastExecutedAt)]">
            {{ formatElapsedTime(event.lastExecutedAt) }}
          </div>

          <!-- サブ情報 -->
          <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <p v-if="event.lastExecutedMemo" class="line-clamp-1">
              メモ: {{ event.lastExecutedMemo }}
            </p>
            <p>{{ formatDate(event.lastExecutedAt) }}</p>
          </div>
        </NuxtLink>
      </div>
    </main>

    <!-- FAB（追加ボタン） -->
    <button
      class="fixed bottom-6 right-6 w-14 h-14 md:w-16 md:h-16 bg-blue-500 hover:bg-blue-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all flex items-center justify-center"
      aria-label="新しいイベントを作成"
      @click="openCreateModal"
    >
      <UIcon name="i-lucide-plus" class="w-6 h-6 md:w-8 md:h-8" />
    </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { differenceInDays, intervalToDuration } from 'date-fns';
import { getCategoryIcon } from '~/constants/categories';
import CreateEventModal from '~/components/EventForm/CreateEventModal.vue';
import SideMenu from '~/components/SideMenu/index.vue';
import { useEventsStore } from '~/stores/events';

// Pinia Store
const eventsStore = useEventsStore();

// リアクティブステート
const userNickname = ref('');
const showCreateModal = ref(false);
const showSideMenu = ref(false);

/**
 * アクティブなフィルターがあるかどうか
 */
const hasActiveFilters = computed(() => {
  return eventsStore.timeFilter !== 'all' || eventsStore.selectedCategories.length > 0;
});

// メソッド
const openSideMenu = () => {
  showSideMenu.value = true;
};

const calculateElapsedDays = (lastExecutedAt: string | null): number | null => {
  if (!lastExecutedAt) return null;
  return differenceInDays(new Date(), new Date(lastExecutedAt));
};

const formatElapsedTime = (lastExecutedAt: string | null): string => {
  if (!lastExecutedAt) return '記録なし';

  const lastDate = new Date(lastExecutedAt);
  const now = new Date();

  const duration = intervalToDuration({
    start: lastDate,
    end: now,
  });

  // 年・月・日の形式でフォーマット
  const parts: string[] = [];
  if (duration.years) parts.push(`${duration.years}年`);
  if (duration.months) parts.push(`${duration.months}ヶ月`);
  if (duration.days) parts.push(`${duration.days}日`);

  // 全てが0の場合（当日）
  if (parts.length === 0) return '0日';

  return parts.join(' ');
};

const ELAPSED_TIME_THRESHOLDS = {
  WEEK: 7,
  MONTH: 30,
  YEAR: 365,
} as const;

const getElapsedTimeColor = (lastExecutedAt: string | null): string => {
  const days = calculateElapsedDays(lastExecutedAt);
  if (days === null) return 'text-gray-500';
  if (days <= ELAPSED_TIME_THRESHOLDS.WEEK) return 'text-green-600 dark:text-green-400';
  if (days <= ELAPSED_TIME_THRESHOLDS.MONTH) return 'text-yellow-600 dark:text-yellow-400';
  if (days <= ELAPSED_TIME_THRESHOLDS.YEAR) return 'text-orange-600 dark:text-orange-400';
  return 'text-red-600 dark:text-red-400';
};

const formatDate = (dateStr: string | null): string => {
  if (!dateStr) return '実行履歴なし';
  try {
    return new Intl.DateTimeFormat('ja-JP', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    }).format(new Date(dateStr));
  } catch {
    return '無効な日付';
  }
};

const openCreateModal = () => {
  showCreateModal.value = true;
};

const handleEventCreated = async () => {
  // イベント作成後、一覧を再取得
  await eventsStore.fetchEvents();
};

const fetchUserInfo = async () => {
  try {
    const token = useCookie('access_token');
    const config = useRuntimeConfig();
    const response = await $fetch<any>('/auth/me', {
      baseURL: config.public.apiBaseUrl,
      headers: {
        Authorization: `Bearer ${token.value}`,
      },
    });

    if (response.success && response.data.user) {
      userNickname.value = response.data.user.nickname;
    }
  }
  catch (err) {
    console.error('Failed to fetch user info:', err);
  }
};

await Promise.all([
  fetchUserInfo(),
  eventsStore.fetchEvents(),
]);
</script>
