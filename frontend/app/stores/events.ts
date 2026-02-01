import { defineStore } from 'pinia';
import { differenceInDays } from 'date-fns';
import type { CategoryType } from '~/constants/categories';

/**
 * 時間フィルターの種類
 */
export type TimeFilterType = 'all' | 'weeks' | 'months' | 'years';

/**
 * イベントデータの型
 */
export interface Event {
  id: number;
  name: string;
  categoryIcon: CategoryType;
  lastExecutedAt: string | null;
  lastExecutedMemo: string | null;
}

/**
 * フィルター統計情報の型
 */
export interface FilterStats {
  all: number;
  weeks: number;
  months: number;
  years: number;
}

/**
 * イベント管理Store
 * - イベント一覧の状態管理
 * - フィルター状態の管理
 * - フィルター統計の計算
 */
export const useEventsStore = defineStore('events', () => {
  // ===== State =====
  /** 全イベント一覧 */
  const allEvents = ref<Event[]>([]);

  /** ローディング状態 */
  const loading = ref(false);

  /** エラーメッセージ */
  const error = ref<string | null>(null);

  /** 時間フィルター */
  const timeFilter = ref<TimeFilterType>('all');

  /** 選択されたカテゴリー */
  const selectedCategories = ref<CategoryType[]>([]);

  /** 検索クエリ */
  const searchQuery = ref('');

  // ===== Getters (Computed) =====

  /**
   * フィルター統計情報を計算
   * 各時間フィルターに該当するイベント数を集計
   */
  const filterStats = computed<FilterStats>(() => {
    const now = new Date();
    return {
      all: allEvents.value.length,
      weeks: allEvents.value.filter((e) => {
        if (!e.lastExecutedAt) return false;
        return differenceInDays(now, new Date(e.lastExecutedAt)) >= 7;
      }).length,
      months: allEvents.value.filter((e) => {
        if (!e.lastExecutedAt) return false;
        return differenceInDays(now, new Date(e.lastExecutedAt)) >= 30;
      }).length,
      years: allEvents.value.filter((e) => {
        if (!e.lastExecutedAt) return false;
        return differenceInDays(now, new Date(e.lastExecutedAt)) >= 365;
      }).length,
    };
  });

  /**
   * フィルター適用後のイベント一覧
   */
  const filteredEvents = computed<Event[]>(() => {
    let events = allEvents.value;

    // 時間フィルター適用
    if (timeFilter.value !== 'all') {
      const now = new Date();
      const daysMap: Record<Exclude<TimeFilterType, 'all'>, number> = {
        weeks: 7,
        months: 30,
        years: 365,
      };
      const days = daysMap[timeFilter.value];

      events = events.filter((e) => {
        if (!e.lastExecutedAt) return false;
        return differenceInDays(now, new Date(e.lastExecutedAt)) >= days;
      });
    }

    // カテゴリーフィルター適用（OR条件）
    if (selectedCategories.value.length > 0) {
      events = events.filter(e =>
        selectedCategories.value.includes(e.categoryIcon),
      );
    }

    // 検索フィルター適用
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase();
      events = events.filter(event =>
        event.name.toLowerCase().includes(query)
        || (event.lastExecutedMemo && event.lastExecutedMemo.toLowerCase().includes(query)),
      );
    }

    return events;
  });

  // ===== Actions =====

  /**
   * イベント一覧を取得
   */
  const fetchEvents = async () => {
    loading.value = true;
    error.value = null;
    const token = useCookie('access_token');

    try {
      const config = useRuntimeConfig();

      const response = await $fetch<any>('/events', {
        baseURL: config.public.apiBaseUrl,
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
      });

      if (response.success) {
        allEvents.value = response.data.events || [];
      }
      else {
        throw new Error('イベントの取得に失敗しました');
      }
    }
    catch (err: any) {
      console.error('Failed to fetch events:', err);

      // 401エラーの場合はログイン画面へ
      if (err.status === 401 || err.statusCode === 401) {
        token.value = null;
        await navigateTo('/login');
        return;
      }

      error.value = 'イベントの読み込みに失敗しました';
    }
    finally {
      loading.value = false;
    }
  };

  /**
   * 時間フィルターを設定
   * 「全て」を選択した場合、カテゴリーフィルターもリセット
   */
  const setTimeFilter = (filter: TimeFilterType) => {
    timeFilter.value = filter;
    if (filter === 'all') {
      selectedCategories.value = [];
    }
  };

  /**
   * カテゴリーフィルターをトグル
   */
  const toggleCategory = (category: CategoryType) => {
    const index = selectedCategories.value.indexOf(category);
    if (index > -1) {
      selectedCategories.value.splice(index, 1);
    }
    else {
      selectedCategories.value.push(category);
    }
  };

  /**
   * 検索クエリを設定
   */
  const setSearchQuery = (query: string) => {
    searchQuery.value = query;
  };

  /**
   * 検索クエリをクリア
   */
  const clearSearchQuery = () => {
    searchQuery.value = '';
  };

  /**
   * フィルターをすべてクリア
   */
  const clearFilters = () => {
    timeFilter.value = 'all';
    selectedCategories.value = [];
  };

  return {
    // State
    allEvents,
    loading,
    error,
    timeFilter,
    selectedCategories,
    searchQuery,
    // Getters
    filterStats,
    filteredEvents,
    // Actions
    fetchEvents,
    setTimeFilter,
    toggleCategory,
    setSearchQuery,
    clearSearchQuery,
    clearFilters,
  };
});
