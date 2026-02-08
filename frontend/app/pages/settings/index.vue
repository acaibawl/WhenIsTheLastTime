<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- ヘッダー -->
    <header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="container mx-auto px-4 py-3 flex items-center justify-between">
        <!-- 戻るボタン -->
        <NuxtLink
          to="/"
          class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          aria-label="戻る"
        >
          <UIcon name="i-lucide-arrow-left" class="w-6 h-6" />
        </NuxtLink>

        <!-- タイトル -->
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white flex-1 text-center">
          設定
        </h1>

        <!-- ダークモード切り替えボタン -->
        <UColorModeButton class="p-2" />
      </div>
    </header>

    <main class="container mx-auto max-w-3xl">
      <!-- ローディング状態 -->
      <div v-if="settingsStore.loading" class="flex justify-center items-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-blue-500" />
      </div>

      <!-- エラー状態 -->
      <div v-else-if="settingsStore.error" class="text-center py-12">
        <UIcon name="i-lucide-alert-circle" class="w-12 h-12 mx-auto text-red-500 mb-4" />
        <p class="text-gray-600 dark:text-gray-400 mb-4">
          {{ settingsStore.error }}
        </p>
        <UButton @click="settingsStore.loadAllSettings()">再読み込み</UButton>
      </div>

      <!-- 設定一覧 -->
      <div v-else>
        <!-- データセクション -->
        <SettingSection title="データ">
          <SettingItem
            label="CSVとしてエクスポート"
            @click="handleExportClick"
          />
        </SettingSection>

        <!-- 表示設定セクション -->
        <SettingSection title="表示設定">
          <SettingItem
            label="ソート順"
            :value="sortOrderLabel"
            to="/settings/sort"
          />
          <SettingItem
            label="時間設定"
            :value="timeOriginLabel"
            to="/settings/time"
          />
          <SettingToggle
            label="タイムフリッパー"
            description="24時間以内は時分秒で表示"
            :model-value="settingsStore.localSettings.display.useTimeFlipper"
            @update:model-value="handleToggleTimeFlipper"
          />
        </SettingSection>

        <!-- 通知セクション -->
        <SettingSection title="通知">
          <SettingItem
            label="リマインダー"
            description="※この機能は準備中です"
            to="/settings/reminder"
          />
        </SettingSection>

        <!-- その他セクション -->
        <SettingSection title="その他">
          <SettingToggle
            label="チュートリアル"
            description="次回起動時にチュートリアルを表示"
            :model-value="settingsStore.serverSettings.misc.showTutorial"
            @update:model-value="handleToggleTutorial"
          />
        </SettingSection>

        <!-- アプリ情報セクション -->
        <SettingSection title="アプリ情報">
          <SettingItem
            label="バージョン"
            :value="appVersion"
            :clickable="false"
          />
          <SettingItem
            label="利用規約"
            to="/terms"
          />
          <SettingItem
            label="プライバシーポリシー"
            to="/privacy"
          />
        </SettingSection>
      </div>
    </main>

    <!-- エクスポート確認モーダル -->
    <UModal v-model:open="showExportModal">
      <template #content>
        <div class="p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            データをエクスポートしますか？
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            すべてのイベントと履歴データがCSV形式でダウンロードされます。
          </p>
          <div class="flex justify-end gap-3">
            <UButton
              variant="ghost"
              color="neutral"
              :disabled="exporting"
              @click="showExportModal = false"
            >
              キャンセル
            </UButton>
            <UButton
              color="primary"
              :loading="exporting"
              @click="handleExport"
            >
              エクスポート
            </UButton>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { useSettingsStore } from '~/stores/settings';
import SettingSection from '~/components/Settings/SettingSection.vue';
import SettingItem from '~/components/Settings/SettingItem.vue';
import SettingToggle from '~/components/Settings/SettingToggle.vue';

const toast = useToast();
const settingsStore = useSettingsStore();

// State
const showExportModal = ref(false);
const exporting = ref(false);
const appVersion = '1.0.0';

// Computed
const sortOrderLabel = computed(() => {
  const labels: Record<string, string> = {
    alphabetical: 'アルファベット順',
    date_desc: '日時降順（最新が上）',
    date_asc: '日時昇順（古いものが上）',
  };
  return labels[settingsStore.localSettings.display.sortOrder] || 'アルファベット順';
});

const timeOriginLabel = computed(() => {
  const labels: Record<string, string> = {
    'midnight': '真夜中を起点とする',
    '24hours': '24時間ごと',
  };
  return labels[settingsStore.localSettings.display.timeOrigin] || '真夜中を起点とする';
});

// Methods
const handleExportClick = () => {
  showExportModal.value = true;
};

const handleExport = async () => {
  const token = useCookie('access_token');
  const config = useRuntimeConfig();

  try {
    exporting.value = true;

    // Fetch API でBlobとしてダウンロード
    const response = await fetch(`${config.public.apiBaseUrl}/export/csv`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token.value}`,
      },
    });

    if (!response.ok) {
      throw new Error('Export failed');
    }

    // Blobとしてレスポンスを取得
    const blob = await response.blob();

    // ファイル名をContent-Dispositionヘッダーから取得
    const contentDisposition = response.headers.get('Content-Disposition');
    let filename = 'when-is-the-last-time.csv';
    if (contentDisposition) {
      const match = contentDisposition.match(/filename="(.+)"/);
      if (match && match[1]) {
        filename = match[1];
      }
    }

    // ダウンロードリンクを作成
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    toast.add({
      title: 'データをエクスポートしました',
      color: 'success',
    });

    showExportModal.value = false;
  } catch (err) {
    console.error('Export failed:', err);
    toast.add({
      title: 'エクスポートに失敗しました',
      color: 'error',
    });
  } finally {
    exporting.value = false;
  }
};

const handleToggleTimeFlipper = () => {
  settingsStore.toggleTimeFlipper();
};

const handleToggleTutorial = async () => {
  const success = await settingsStore.toggleTutorial();
  if (success && settingsStore.serverSettings.misc.showTutorial) {
    toast.add({
      title: '次回起動時にチュートリアルが表示されます',
      color: 'info',
    });
  }
};

settingsStore.loadAllSettings();
</script>
