<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- ヘッダー -->
    <header class="sticky top-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="container mx-auto px-4 py-3 flex items-center justify-between">
        <!-- ハンバーガーメニュー（将来実装） -->
        <button class="p-2 text-gray-600 dark:text-gray-300">
          <UIcon name="i-lucide-menu" class="w-6 h-6" />
        </button>

        <!-- タイトル -->
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white flex-1 text-center">
          最後はいつ？
        </h1>

        <!-- ニックネーム -->
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600 dark:text-gray-300 max-w-[100px] truncate">
            👤 {{ userNickname }}
          </span>

          <!-- 検索アイコン -->
          <button class="p-2 text-gray-600 dark:text-gray-300" @click="toggleSearch">
            <UIcon name="i-lucide-search" class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- 検索バー -->
      <div v-if="showSearch" class="border-t border-gray-200 dark:border-gray-700 px-4 py-2">
        <UInput
          v-model="searchQuery"
          placeholder="イベントを検索..."
          icon="i-lucide-search"
          :trailing="true"
          size="lg"
        >
          <template #trailing>
            <UButton
              v-if="searchQuery"
              color="neutral"
              variant="link"
              icon="i-lucide-x"
              size="xs"
              @click="clearSearch"
            />
          </template>
        </UInput>
      </div>
    </header>

    <!-- メインコンテンツ -->
    <main class="container mx-auto px-4 py-6 pb-24">
      <!-- ローディング状態 -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-blue-500" />
      </div>

      <!-- エラー状態 -->
      <div v-else-if="error" class="text-center py-12">
        <UIcon name="i-lucide-alert-circle" class="w-12 h-12 mx-auto text-red-500 mb-4" />
        <p class="text-gray-600 dark:text-gray-400 mb-4">
          {{ error }}
        </p>
        <UButton @click="fetchEvents">再読み込み</UButton>
      </div>

      <!-- 空の状態 -->
      <div v-else-if="filteredEvents.length === 0" class="text-center py-12">
        <UIcon name="i-lucide-file-text" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
          {{ searchQuery ? '該当するイベントがありません' : 'イベントがありません' }}
        </h2>
        <p class="text-gray-500 dark:text-gray-400 mb-6">
          {{ searchQuery ? '別のキーワードで検索してみてください' : '「+」ボタンから最初のイベントを追加しましょう' }}
        </p>
      </div>

      <!-- イベント一覧 -->
      <div v-else class="space-y-3">
        <div
          v-for="event in filteredEvents"
          :key="event.id"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer active:scale-[0.98] transition-transform"
          @click="navigateToHistory(event.id)"
        >
          <!-- イベント名 -->
          <div class="flex items-start gap-3 mb-2">
            <span class="text-2xl">{{ event.icon || '📌' }}</span>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex-1 line-clamp-2">
              {{ event.name }}
            </h3>
          </div>

          <!-- 経過時間 -->
          <div :class="['text-2xl font-bold mb-2', getElapsedTimeColor(event.elapsed_days)]">
            {{ formatElapsedTime(event.elapsed_days) }}
          </div>

          <!-- サブ情報 -->
          <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <p v-if="event.last_memo" class="line-clamp-1">
              メモ: {{ event.last_memo }}
            </p>
            <p>{{ formatDate(event.last_executed_at) }}</p>
          </div>
        </div>
      </div>
    </main>

    <!-- FAB（追加ボタン） -->
    <button
      class="fixed bottom-6 right-6 w-14 h-14 md:w-16 md:h-16 bg-blue-500 hover:bg-blue-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all flex items-center justify-center"
      @click="navigateToCreate"
    >
      <UIcon name="i-lucide-plus" class="w-6 h-6 md:w-8 md:h-8" />
    </button>
  </div>
</template>

<script setup lang="ts">
// ページメタデータ（認証ミドルウェアを適用）
definePageMeta({
  middleware: 'auth'
});

// 型定義
interface Event {
  id: number;
  name: string;
  icon: string | null;
  last_executed_at: string | null;
  last_memo: string | null;
  elapsed_days: number;
}

// リアクティブステート
const loading = ref(true);
const error = ref<string | null>(null);
const events = ref<Event[]>([]);
const searchQuery = ref('');
const showSearch = ref(false);
const userNickname = ref('ゲスト');

// 計算プロパティ
const filteredEvents = computed(() => {
  if (!searchQuery.value) return events.value;

  const query = searchQuery.value.toLowerCase();
  return events.value.filter(event =>
    event.name.toLowerCase().includes(query) ||
    (event.last_memo && event.last_memo.toLowerCase().includes(query))
  );
});

// メソッド
const toggleSearch = () => {
  showSearch.value = !showSearch.value;
  if (!showSearch.value) {
    searchQuery.value = '';
  }
};

const clearSearch = () => {
  searchQuery.value = '';
};

const fetchEvents = async () => {
  loading.value = true;
  error.value = null;

  try {
    const token = useCookie('access_token');
    const config = useRuntimeConfig();

    // イベント一覧取得
    const response = await $fetch<any>('/events', {
      baseURL: config.public.apiBaseUrl,
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    });

    if (response.success) {
      events.value = response.data.events || [];
    } else {
      throw new Error('イベントの取得に失敗しました');
    }
  } catch (err: any) {
    console.error('Failed to fetch events:', err);

    // 401エラーの場合はログイン画面へ
    if (err.status === 401 || err.statusCode === 401) {
      const token = useCookie('access_token');
      token.value = null;
      await navigateTo('/login');
      return;
    }

    error.value = 'イベントの読み込みに失敗しました';
  } finally {
    loading.value = false;
  }
};

const formatElapsedTime = (days: number): string => {
  if (days === null || days === undefined) return '記録なし';

  const years = Math.floor(days / 365);
  const months = Math.floor((days % 365) / 30);
  const remainingDays = days % 30;

  return `${years}年 ${months}ヶ月 ${remainingDays}日`;
};

const getElapsedTimeColor = (days: number): string => {
  if (days === null || days === undefined) return 'text-gray-500';
  if (days <= 7) return 'text-green-600 dark:text-green-400';
  if (days <= 30) return 'text-yellow-600 dark:text-yellow-400';
  if (days <= 365) return 'text-orange-600 dark:text-orange-400';
  return 'text-red-600 dark:text-red-400';
};

const formatDate = (dateStr: string | null): string => {
  if (!dateStr) return '実行履歴なし';

  const date = new Date(dateStr);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}/${month}/${day}`;
};

const navigateToHistory = (eventId: number) => {
  // TODO: イベント履歴画面への遷移（未実装）
  console.log('Navigate to event history:', eventId);
};

const navigateToCreate = () => {
  // TODO: イベント作成画面への遷移（未実装）
  console.log('Navigate to create event');
};

// ライフサイクルフック
onMounted(async () => {
  // ユーザー情報の取得（簡易実装）
  try {
    const token = useCookie('access_token');
    const config = useRuntimeConfig();
    const response = await $fetch<any>('/auth/me', {
      baseURL: config.public.apiBaseUrl,
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    });

    if (response.success && response.data.user) {
      userNickname.value = response.data.user.nickname || 'ゲスト';
    }
  } catch (err) {
    console.error('Failed to fetch user info:', err);
  }

  // イベント一覧取得
  await fetchEvents();
});
</script>
