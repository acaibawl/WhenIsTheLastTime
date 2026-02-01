import type { CategoryType } from '~/constants/categories';
import type { Event } from '~/types/eventHistory';

interface UpdateEventPayload {
  name: string;
  categoryIcon: CategoryType;
}

interface EventEditFormErrors {
  eventName?: string;
  general?: string;
}

/**
 * イベント編集フォームのロジックを管理するComposable
 */
export const useEventEditForm = (eventId: string | number) => {
  // フォーム入力値
  const eventName = ref('');
  const selectedIcon = ref<CategoryType>('pin');

  // 初期値（変更検知用）
  const initialEventName = ref('');
  const initialIcon = ref<CategoryType>('pin');

  // UI状態
  const isSaving = ref(false);
  const errors = ref<EventEditFormErrors>({});

  const config = useRuntimeConfig();
  const token = useCookie('access_token');

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
      eventName.value.trim() !== initialEventName.value.trim()
      || selectedIcon.value !== initialIcon.value
    );
  });

  // イベントデータでフォームを初期化
  const initializeWithEvent = (event: Event) => {
    eventName.value = event.name;
    selectedIcon.value = event.categoryIcon;
    initialEventName.value = event.name;
    initialIcon.value = event.categoryIcon;
    errors.value = {};
  };

  // 保存処理
  const save = async (): Promise<Event | null> => {
    if (!validate()) {
      return null;
    }

    isSaving.value = true;
    errors.value = {};

    try {
      const payload: UpdateEventPayload = {
        name: eventName.value.trim(),
        categoryIcon: selectedIcon.value,
      };

      const response = await $fetch<any>(`/events/${eventId}`, {
        method: 'PUT',
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
          title: 'イベントを更新しました',
          color: 'success',
          icon: 'i-lucide-check-circle',
        });

        // 初期値を更新
        initialEventName.value = eventName.value.trim();
        initialIcon.value = selectedIcon.value;

        return response.data.event;
      } else {
        throw new Error('イベントの更新に失敗しました');
      }
    } catch (error: any) {
      console.error('Failed to update event:', error);

      // 401エラーの場合はログイン画面へ
      if (error.status === 401 || error.statusCode === 401) {
        token.value = null;
        await navigateTo('/login');
        return null;
      }

      errors.value.general = error.data?.error?.message || 'イベントの更新に失敗しました';

      // トースト通知
      const toast = useToast();
      toast.add({
        title: 'エラー',
        description: errors.value.general,
        color: 'error',
        icon: 'i-lucide-alert-circle',
      });

      return null;
    } finally {
      isSaving.value = false;
    }
  };

  // フォームのリセット（初期値に戻す）
  const reset = () => {
    eventName.value = initialEventName.value;
    selectedIcon.value = initialIcon.value;
    errors.value = {};
  };

  return {
    // フォーム入力値
    eventName,
    selectedIcon,

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
    initializeWithEvent,
  };
};
