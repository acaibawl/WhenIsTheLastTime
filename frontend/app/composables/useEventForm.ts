// 型定義
export type CategoryType
  = | 'pin'
    | 'book'
    | 'folder'
    | 'star'
    | 'chart'
    | 'sun'
    | 'person'
    | 'hospital'
    | 'medical'
    | 'leaf'
    | 'search'
    | 'people'
    | 'snowflake'
    | 'fire'
    | 'lightning';

interface CreateEventPayload {
  name: string;
  categoryIcon: CategoryType;
  executedAt: string; // ISO 8601形式
  memo?: string;
}

interface EventFormErrors {
  eventName?: string;
  general?: string;
}

/**
 * イベント作成フォームのロジックを管理するComposable
 */
export const useEventForm = () => {
  // フォーム入力値
  const eventName = ref('');
  const selectedIcon = ref<CategoryType>('pin');
  const selectedDate = ref('');
  const selectedTime = ref('');
  const memo = ref('');

  // UI状態
  const isSaving = ref(false);
  const errors = ref<EventFormErrors>({});

  // バリデーション
  const validate = (): boolean => {
    errors.value = {};

    // イベント名のバリデーション
    if (!eventName.value.trim()) {
      errors.value.eventName = 'イベント名を入力してください';
      return false;
    }

    if (eventName.value.length > 100) {
      errors.value.eventName = 'イベント名は100文字以内で入力してください';
      return false;
    }

    return true;
  };

  // フォームの妥当性
  const isValid = computed(() => {
    return eventName.value.trim().length > 0 && eventName.value.length <= 100;
  });

  // 変更検知
  const hasChanges = computed(() => {
    return (
      eventName.value.trim().length > 0
      || selectedIcon.value !== 'pin'
      || memo.value.trim().length > 0
    );
  });

  // 現在日時を取得してフォーマット（デフォルト値用）
  const initializeDateTime = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');

    selectedDate.value = `${year}-${month}-${day}`;
    selectedTime.value = `${hours}:${minutes}`;
  };

  // 日付と時刻を結合してISO 8601形式に変換
  const getExecutedAtISO = (): string => {
    // 日付と時刻を結合（秒を含める）
    const dateTimeString = `${selectedDate.value}T${selectedTime.value}:00`;

    // Dateオブジェクトを作成（ローカルタイムとして解釈される）
    const date = new Date(dateTimeString);

    // ISO 8601形式（UTC）に変換してミリ秒を除去
    // toISOString() は "2026-01-29T01:30:00.000Z" を返すので、".000" を除去
    // 結果: "2026-01-29T01:30:00Z"
    return date.toISOString().replace(/\.\d{3}Z$/, 'Z');
  };

  // 保存処理
  const save = async (): Promise<boolean> => {
    if (!validate()) {
      return false;
    }

    isSaving.value = true;
    errors.value = {};

    try {
      const token = useCookie('access_token');
      const config = useRuntimeConfig();

      const payload: CreateEventPayload = {
        name: eventName.value.trim(),
        categoryIcon: selectedIcon.value,
        executedAt: getExecutedAtISO(),
      };

      // メモが入力されている場合のみ追加
      if (memo.value.trim()) {
        payload.memo = memo.value.trim();
      }

      const response = await $fetch<any>('/events', {
        method: 'POST',
        baseURL: config.public.apiBaseUrl,
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
        body: payload,
      });

      if (response.success) {
        // トースト通知
        const toast = useToast();
        toast.add({
          title: 'イベントを作成しました',
          color: 'info',
          icon: 'i-lucide-check-circle',
        });
        return true;
      } else {
        throw new Error('イベントの作成に失敗しました');
      }
    } catch (error: any) {
      console.error('Failed to create event:', error);

      // 401エラーの場合はログイン画面へ
      if (error.status === 401 || error.statusCode === 401) {
        const token = useCookie('access_token');
        token.value = null;
        await navigateTo('/login');
        return false;
      }

      errors.value.general = error.data?.error?.message || 'イベントの作成に失敗しました';

      // トースト通知
      const toast = useToast();
      toast.add({
        title: 'エラー',
        description: errors.value.general,
        color: 'error',
        icon: 'i-lucide-alert-circle',
      });

      return false;
    } finally {
      isSaving.value = false;
    }
  };

  // フォームのリセット
  const reset = () => {
    eventName.value = '';
    selectedIcon.value = 'pin';
    memo.value = '';
    initializeDateTime();
    errors.value = {};
  };

  // 初期化
  onMounted(() => {
    initializeDateTime();
  });

  return {
    // フォーム入力値
    eventName,
    selectedIcon,
    selectedDate,
    selectedTime,
    memo,

    // UI状態
    isSaving,
    errors,

    // 計算プロパティ
    isValid,
    hasChanges,

    // メソッド
    validate,
    save,
    reset,
    initializeDateTime,
  };
};
