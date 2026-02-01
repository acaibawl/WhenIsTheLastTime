import { defineStore } from 'pinia';

/**
 * ソート順
 */
export type SortOrder = 'alphabetical' | 'date_desc' | 'date_asc';

/**
 * 時間起点
 */
export type TimeOrigin = 'midnight' | '24hours';

/**
 * ローカル設定（ローカルストレージで管理）
 */
export interface LocalSettings {
  display: {
    sortOrder: SortOrder;
    timeOrigin: TimeOrigin;
    useTimeFlipper: boolean;
  };
}

/**
 * リマインダー設定
 */
export interface ReminderSettings {
  enabled: boolean;
  timing: {
    type: 'daily' | 'weekly' | 'monthly';
    time: string;
    dayOfWeek?: number | null;
    dayOfMonth?: number | null;
  };
  targetEvents: 'all' | 'week' | 'month' | 'year';
}

/**
 * サーバー設定（APIで管理）
 */
export interface ServerSettings {
  export: {
    lastExportedAt?: string | null;
  };
  notification: {
    reminder: ReminderSettings;
  };
  misc: {
    showTutorial: boolean;
  };
}

const LOCAL_STORAGE_KEY = 'when-is-the-last-time:local-settings';

/**
 * デフォルトのローカル設定
 */
const DEFAULT_LOCAL_SETTINGS: LocalSettings = {
  display: {
    sortOrder: 'alphabetical',
    timeOrigin: 'midnight',
    useTimeFlipper: false,
  },
};

/**
 * デフォルトのサーバー設定
 */
const DEFAULT_SERVER_SETTINGS: ServerSettings = {
  export: {
    lastExportedAt: null,
  },
  notification: {
    reminder: {
      enabled: false,
      timing: {
        type: 'daily',
        time: '09:00',
        dayOfWeek: null,
        dayOfMonth: null,
      },
      targetEvents: 'week',
    },
  },
  misc: {
    showTutorial: true,
  },
};

/**
 * 設定管理Store
 */
export const useSettingsStore = defineStore('settings', () => {
  // ===== State =====
  /** ローカル設定 */
  const localSettings = ref<LocalSettings>({ ...DEFAULT_LOCAL_SETTINGS });

  /** サーバー設定 */
  const serverSettings = ref<ServerSettings>({ ...DEFAULT_SERVER_SETTINGS });

  /** ローディング状態 */
  const loading = ref(false);

  /** エラーメッセージ */
  const error = ref<string | null>(null);

  // ===== ローカル設定の読み込み・保存 =====

  /**
   * ローカル設定を読み込み
   */
  const loadLocalSettings = () => {
    if (import.meta.client) {
      try {
        const stored = localStorage.getItem(LOCAL_STORAGE_KEY);
        if (stored) {
          const parsed = JSON.parse(stored);
          localSettings.value = {
            display: {
              ...DEFAULT_LOCAL_SETTINGS.display,
              ...parsed.display,
            },
          };
        }
      } catch (err) {
        console.error('Failed to load local settings:', err);
        localSettings.value = { ...DEFAULT_LOCAL_SETTINGS };
      }
    }
  };

  /**
   * ローカル設定を保存
   */
  const saveLocalSettings = () => {
    if (import.meta.client) {
      try {
        localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(localSettings.value));
      } catch (err) {
        console.error('Failed to save local settings:', err);
      }
    }
  };

  // ===== サーバー設定の読み込み・更新 =====

  /**
   * サーバー設定を読み込み
   */
  const loadServerSettings = async () => {
    const token = useCookie('access_token');
    const config = useRuntimeConfig();

    try {
      loading.value = true;
      error.value = null;

      const response = await $fetch<{
        success: boolean;
        data: {
          settings: ServerSettings;
        };
      }>('/settings', {
        baseURL: config.public.apiBaseUrl,
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
      });

      if (response.success) {
        serverSettings.value = {
          export: {
            ...DEFAULT_SERVER_SETTINGS.export,
            ...response.data.settings.export,
          },
          notification: {
            reminder: {
              ...DEFAULT_SERVER_SETTINGS.notification.reminder,
              ...response.data.settings.notification?.reminder,
              timing: {
                ...DEFAULT_SERVER_SETTINGS.notification.reminder.timing,
                ...response.data.settings.notification?.reminder?.timing,
              },
            },
          },
          misc: {
            ...DEFAULT_SERVER_SETTINGS.misc,
            ...response.data.settings.misc,
          },
        };
      }
    } catch (err) {
      console.error('Failed to load server settings:', err);
      error.value = '設定の読み込みに失敗しました';
      serverSettings.value = { ...DEFAULT_SERVER_SETTINGS };
    } finally {
      loading.value = false;
    }
  };

  /**
   * サーバー設定を更新
   */
  const updateServerSettings = async (updates: Partial<ServerSettings>): Promise<boolean> => {
    const token = useCookie('access_token');
    const config = useRuntimeConfig();

    try {
      loading.value = true;
      error.value = null;

      const response = await $fetch<{
        success: boolean;
        data: {
          settings: ServerSettings;
        };
      }>('/settings', {
        method: 'PATCH',
        baseURL: config.public.apiBaseUrl,
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
        body: updates,
      });

      if (response.success) {
        serverSettings.value = {
          export: {
            ...DEFAULT_SERVER_SETTINGS.export,
            ...response.data.settings.export,
          },
          notification: {
            reminder: {
              ...DEFAULT_SERVER_SETTINGS.notification.reminder,
              ...response.data.settings.notification?.reminder,
              timing: {
                ...DEFAULT_SERVER_SETTINGS.notification.reminder.timing,
                ...response.data.settings.notification?.reminder?.timing,
              },
            },
          },
          misc: {
            ...DEFAULT_SERVER_SETTINGS.misc,
            ...response.data.settings.misc,
          },
        };
        return true;
      }
      return false;
    } catch (err) {
      console.error('Failed to update server settings:', err);
      error.value = '設定の更新に失敗しました';
      return false;
    } finally {
      loading.value = false;
    }
  };

  // ===== すべての設定を読み込み =====

  /**
   * すべての設定を読み込み
   */
  const loadAllSettings = async () => {
    loadLocalSettings();
    await loadServerSettings();
  };

  // ===== ローカル設定の更新ヘルパー =====

  /**
   * ソート順を更新
   */
  const updateSortOrder = (sortOrder: SortOrder) => {
    localSettings.value.display.sortOrder = sortOrder;
    saveLocalSettings();
  };

  /**
   * 時間設定を更新
   */
  const updateTimeOrigin = (timeOrigin: TimeOrigin) => {
    localSettings.value.display.timeOrigin = timeOrigin;
    saveLocalSettings();
  };

  /**
   * タイムフリッパーを切り替え
   */
  const toggleTimeFlipper = () => {
    localSettings.value.display.useTimeFlipper = !localSettings.value.display.useTimeFlipper;
    saveLocalSettings();
  };

  // ===== サーバー設定の更新ヘルパー =====

  /**
   * チュートリアルを切り替え
   */
  const toggleTutorial = async (): Promise<boolean> => {
    const newValue = !serverSettings.value.misc.showTutorial;
    return await updateServerSettings({
      misc: {
        showTutorial: newValue,
      },
    });
  };

  /**
   * リマインダー設定を更新
   */
  const updateReminderSettings = async (reminder: ReminderSettings): Promise<boolean> => {
    return await updateServerSettings({
      notification: {
        reminder,
      },
    });
  };

  return {
    // State
    localSettings: readonly(localSettings),
    serverSettings: readonly(serverSettings),
    loading: readonly(loading),
    error: readonly(error),

    // Actions
    loadAllSettings,
    loadLocalSettings,
    loadServerSettings,
    updateSortOrder,
    updateTimeOrigin,
    toggleTimeFlipper,
    toggleTutorial,
    updateReminderSettings,
    updateServerSettings,
  };
});
