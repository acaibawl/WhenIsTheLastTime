import { intervalToDuration, differenceInDays, differenceInWeeks, differenceInMonths, differenceInYears } from 'date-fns';
import type { Event, History, Statistics, GroupedHistory } from '~~/app/types/eventHistory';

export const useEventHistory = (eventId: string | number) => {
  const event = ref<Event | null>(null);
  const histories = ref<History[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const showMenu = ref(false);
  const showDeleteDialog = ref(false);

  const config = useRuntimeConfig();
  const token = useCookie('access_token');

  // イベント情報と履歴を取得
  const loadData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
      // 並列で取得
      const [eventResponse, historyResponse] = await Promise.all([
        $fetch<any>(`/events/${eventId}`, {
          baseURL: config.public.apiBaseUrl,
          headers: {
            Authorization: `Bearer ${token.value}`,
          },
        }),
        $fetch<any>(`/events/${eventId}/history`, {
          baseURL: config.public.apiBaseUrl,
          headers: {
            Authorization: `Bearer ${token.value}`,
          },
        }),
      ]);

      if (eventResponse.success) {
        event.value = eventResponse.data.event;
      } else {
        throw new Error('イベントの取得に失敗しました');
      }

      if (historyResponse.success) {
        histories.value = historyResponse.data.histories || [];
      } else {
        throw new Error('履歴の取得に失敗しました');
      }
    } catch (err: any) {
      console.error('Failed to load data:', err);

      // 401エラーの場合はログイン画面へ
      if (err.status === 401 || err.statusCode === 401) {
        token.value = null;
        await navigateTo('/login');
        return;
      }

      error.value = 'データの読み込みに失敗しました';
    } finally {
      isLoading.value = false;
    }
  };

  // 統計情報を計算
  const statistics = computed<Statistics>(() => {
    const now = new Date();
    const startOfWeek = new Date(now);
    startOfWeek.setDate(now.getDate() - now.getDay() + (now.getDay() === 0 ? -6 : 1)); // 月曜日
    startOfWeek.setHours(0, 0, 0, 0);

    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);

    const thisWeek = histories.value.filter((h: History) => {
      const executedDate = new Date(h.executedAt);
      return executedDate >= startOfWeek;
    }).length;

    const thisMonth = histories.value.filter((h: History) => {
      const executedDate = new Date(h.executedAt);
      return executedDate >= startOfMonth;
    }).length;

    return {
      thisWeek,
      thisMonth,
      total: histories.value.length,
      averageInterval: calculateAverageInterval(histories.value),
      averageDays: calculateAverageDays(histories.value),
    };
  });

  // 平均間隔を計算
  const calculateAverageInterval = (historyList: History[]): string => {
    const avgDays = calculateAverageDays(historyList);
    if (avgDays === 0) return '-';
    return formatDuration(avgDays);
  };

  // 平均日数を計算
  const calculateAverageDays = (historyList: History[]): number => {
    if (historyList.length < 2) return 0;

    const sortedHistories = [...historyList].sort((a, b) =>
      new Date(b.executedAt).getTime() - new Date(a.executedAt).getTime(),
    );

    let totalDays = 0;
    for (let i = 0; i < sortedHistories.length - 1; i++) {
      const diff = differenceInDays(
        new Date(sortedHistories[i]!.executedAt),
        new Date(sortedHistories[i + 1]!.executedAt),
      );
      totalDays += diff;
    }

    return Math.round(totalDays / (sortedHistories.length - 1));
  };

  // 期間のフォーマット
  const formatDuration = (days: number): string => {
    const duration = intervalToDuration({ start: 0, end: days * 24 * 60 * 60 * 1000 });

    const parts: string[] = [];
    if (duration.years) parts.push(`${duration.years}年`);
    if (duration.months) parts.push(`${duration.months}ヶ月`);
    if (duration.days) parts.push(`${duration.days}日`);

    if (parts.length === 0) {
      return '0日ごと';
    }

    return parts.join('') + 'ごと';
  };

  // 年月でグループ化
  const groupedHistories = computed<GroupedHistory[]>(() => {
    const groups: Record<string, History[]> = {};

    histories.value.forEach((history: History) => {
      const date = new Date(history.executedAt);
      const yearMonth = `${date.getFullYear()}年${String(date.getMonth() + 1).padStart(2, '0')}月`;

      if (!groups[yearMonth]) {
        groups[yearMonth] = [];
      }
      groups[yearMonth].push(history);
    });

    // 各グループ内を日付順にソート（新しい順）
    Object.keys(groups).forEach((key) => {
      groups[key]!.sort((a, b) =>
        new Date(b.executedAt).getTime() - new Date(a.executedAt).getTime(),
      );
    });

    // 降順にソート
    return Object.entries(groups)
      .sort((a, b) => b[0].localeCompare(a[0]))
      .map(([yearMonth, historiesList]) => ({
        yearMonth,
        histories: historiesList,
      }));
  });

  // イベント削除
  const deleteEvent = async () => {
    try {
      const response = await $fetch<any>(`/events/${eventId}`, {
        method: 'DELETE',
        baseURL: config.public.apiBaseUrl,
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
      });

      if (response.success) {
        const toast = useToast();
        toast.add({
          title: 'イベントを削除しました',
          color: 'info',
        });
        await navigateTo('/');
      } else {
        throw new Error('イベントの削除に失敗しました');
      }
    } catch (err) {
      console.error('Failed to delete event:', err);
      error.value = 'イベントの削除に失敗しました';
    }
  };

  // 履歴削除可能かどうか（履歴が2件以上の場合のみ削除可能）
  const canDeleteHistory = computed(() => {
    return histories.value.length > 1;
  });

  // 履歴削除
  const deleteHistory = async (historyId: number) => {
    // 履歴が1件のみの場合は削除不可
    if (!canDeleteHistory.value) {
      const toast = useToast();
      toast.add({
        title: '最後の履歴は削除できません',
        description: 'イベントには最低1件の履歴が必要です。',
        color: 'warning',
      });
      return false;
    }

    try {
      const response = await $fetch<any>(`/events/${eventId}/history/${historyId}`, {
        method: 'DELETE',
        baseURL: config.public.apiBaseUrl,
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
      });

      if (response.success) {
        // ローカルの履歴リストから削除
        histories.value = histories.value.filter(h => h.id !== historyId);

        const toast = useToast();
        toast.add({
          title: '履歴を削除しました',
          color: 'info',
        });
        return true;
      } else {
        throw new Error(response.message || '履歴の削除に失敗しました');
      }
    } catch (err: any) {
      console.error('Failed to delete history:', err);

      // 401エラーの場合はログイン画面へ
      if (err.status === 401 || err.statusCode === 401) {
        token.value = null;
        await navigateTo('/login');
        return false;
      }

      const toast = useToast();
      toast.add({
        title: '履歴の削除に失敗しました',
        description: err.data?.message || err.message || 'エラーが発生しました',
        color: 'error',
      });
      return false;
    }
  };

  // 経過時間のフォーマット
  const formatElapsedTime = (executedAt: string): string => {
    const now = new Date();
    const executedDate = new Date(executedAt);

    const days = differenceInDays(now, executedDate);
    const weeks = differenceInWeeks(now, executedDate);
    const months = differenceInMonths(now, executedDate);
    const years = differenceInYears(now, executedDate);

    if (days === 0) return '今日';
    if (days === 1) return '昨日';
    if (days < 7) return `${days}日前`;
    if (weeks < 4) return `${weeks}週間前`;
    if (months < 12) return `${months}ヶ月前`;
    return `${years}年前`;
  };

  // 履歴追加
  const addHistory = async (executedAt: string, memo?: string): Promise<boolean> => {
    try {
      const response = await $fetch<any>(`/events/${eventId}/history`, {
        method: 'POST',
        baseURL: config.public.apiBaseUrl,
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
        body: {
          executedAt: executedAt,
          memo: memo || null,
        },
      });

      if (response.success) {
        // ローカルの履歴リストに追加
        const newHistory = response.data.history;
        histories.value = [...histories.value, newHistory];

        const toast = useToast();
        toast.add({
          title: '履歴を追加しました',
          color: 'success',
        });
        return true;
      } else {
        throw new Error(response.message || '履歴の追加に失敗しました');
      }
    } catch (err: any) {
      console.error('Failed to add history:', err);

      // 401エラーの場合はログイン画面へ
      if (err.status === 401 || err.statusCode === 401) {
        token.value = null;
        await navigateTo('/login');
        return false;
      }

      const toast = useToast();
      toast.add({
        title: '履歴の追加に失敗しました',
        description: err.data?.message || err.message || 'エラーが発生しました',
        color: 'error',
      });
      return false;
    }
  };

  // 履歴更新
  const updateHistory = async (historyId: number, executedAt: string, memo?: string): Promise<boolean> => {
    try {
      const response = await $fetch<any>(`/events/${eventId}/history/${historyId}`, {
        method: 'PUT',
        baseURL: config.public.apiBaseUrl,
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
        body: {
          executedAt: executedAt,
          memo: memo || null,
        },
      });

      if (response.success) {
        // ローカルの履歴リストを更新
        const updatedHistory = response.data.history;
        histories.value = histories.value.map(h =>
          h.id === historyId ? updatedHistory : h,
        );

        const toast = useToast();
        toast.add({
          title: '履歴を更新しました',
          color: 'success',
        });
        return true;
      } else {
        throw new Error(response.message || '履歴の更新に失敗しました');
      }
    } catch (err: any) {
      console.error('Failed to update history:', err);

      // 401エラーの場合はログイン画面へ
      if (err.status === 401 || err.statusCode === 401) {
        token.value = null;
        await navigateTo('/login');
        return false;
      }

      const toast = useToast();
      toast.add({
        title: '履歴の更新に失敗しました',
        description: err.data?.message || err.message || 'エラーが発生しました',
        color: 'error',
      });
      return false;
    }
  };

  return {
    event,
    histories,
    isLoading,
    error,
    statistics,
    groupedHistories,
    showMenu,
    showDeleteDialog,
    canDeleteHistory,
    loadData,
    deleteEvent,
    deleteHistory,
    addHistory,
    updateHistory,
    formatElapsedTime,
  };
};
