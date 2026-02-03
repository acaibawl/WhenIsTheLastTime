import { defineStore } from 'pinia';
import { differenceInDays, startOfDay } from 'date-fns';
import type { CategoryType } from '~/constants/categories';
import { useSettingsStore, type SortOrder } from '~/stores/settings';

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
   * 時刻は無視して日付のみで比較
   */
  const filterStats = computed<FilterStats>(() => {
    const today = startOfDay(new Date());
    const stats = allEvents.value.reduce(
      (acc, e) => {
        if (!e.lastExecutedAt) {
          return acc;
        }
        const eventDate = startOfDay(new Date(e.lastExecutedAt));
        const days = differenceInDays(today, eventDate);
        if (days >= 7) {
          acc.weeks++;
        }
        if (days >= 30) {
          acc.months++;
        }
        if (days >= 365) {
          acc.years++;
        }
        return acc;
      },
      { weeks: 0, months: 0, years: 0 },
    );

    return {
      all: allEvents.value.length,
      ...stats,
    };
  });

  /**
   * フィルター適用後のイベント一覧
   */
  const filteredEvents = computed<Event[]>(() => {
    let events = allEvents.value;

    // 時間フィルター適用（時刻は無視して日付のみで比較）
    if (timeFilter.value !== 'all') {
      const today = startOfDay(new Date());
      const daysMap: Record<Exclude<TimeFilterType, 'all'>, number> = {
        weeks: 7,
        months: 30,
        years: 365,
      };
      const days = daysMap[timeFilter.value];

      events = events.filter((e) => {
        if (!e.lastExecutedAt) return false;
        const eventDate = startOfDay(new Date(e.lastExecutedAt));
        return differenceInDays(today, eventDate) >= days;
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

    // ソート適用（設定画面のソート順に従う）
    const settingsStore = useSettingsStore();
    const sortOrder: SortOrder = settingsStore.localSettings.display.sortOrder;

    events = [...events].sort((a, b) => {
      switch (sortOrder) {
        case 'alphabetical':
          // アルファベット順（日本語対応のためlocaleCompareを使用）
          return a.name.localeCompare(b.name, 'ja');

        case 'date_desc':
          // 日時降順（最新が上）
          // lastExecutedAtがnullのものは後ろに配置
          if (!a.lastExecutedAt && !b.lastExecutedAt) return 0;
          if (!a.lastExecutedAt) return 1;
          if (!b.lastExecutedAt) return -1;
          return new Date(b.lastExecutedAt).getTime() - new Date(a.lastExecutedAt).getTime();

        case 'date_asc':
          // 日時昇順（古いものが上）
          // lastExecutedAtがnullのものは後ろに配置
          if (!a.lastExecutedAt && !b.lastExecutedAt) return 0;
          if (!a.lastExecutedAt) return 1;
          if (!b.lastExecutedAt) return -1;
          return new Date(a.lastExecutedAt).getTime() - new Date(b.lastExecutedAt).getTime();

        default:
          return 0;
      }
    });

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
      } else {
        throw new Error('イベントの取得に失敗しました');
      }
    } catch (err: any) {
      console.error('Failed to fetch events:', err);

      // 401エラーの場合はログイン画面へ
      if (err.status === 401 || err.statusCode === 401) {
        token.value = null;
        await navigateTo('/login');
        return;
      }

      error.value = 'イベントの読み込みに失敗しました';
    } finally {
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
    } else {
      selectedCategories.value.push(category);
    }
  };

  /**
   * 検索クエリをクリア
   */
  const clearSearchQuery = () => {
    searchQuery.value = '';
  };

  /**
   * カテゴリーフィルターをクリア
   */
  const clearCategories = () => {
    selectedCategories.value = [];
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
    clearSearchQuery,
    clearCategories,
    clearFilters,
  };
});
