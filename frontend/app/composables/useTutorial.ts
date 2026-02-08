import { useSettingsStore } from '~/stores/settings';

/**
 * チュートリアルの各ステップ定義
 */
export interface TutorialStep {
  /** ステップのタイトル */
  title: string;
  /** ステップの説明文 */
  description: string;
  /** 表示するアイコン（Lucide icon名） */
  icon: string;
  /** イラスト用の絵文字 */
  emoji: string;
}

/**
 * チュートリアルのステップ一覧
 */
export const TUTORIAL_STEPS: TutorialStep[] = [
  {
    title: '「最後はいつ？」へようこそ！',
    description:
      'このアプリは、日常の活動を記録して「最後にやったのはいつ？」をかんたんに管理できるアプリです。\nやり忘れを防いで、毎日の習慣をしっかり把握しましょう。',
    icon: 'i-lucide-hand-metal',
    emoji: '👋',
  },
  {
    title: 'イベントを追加する',
    description:
      '画面右下の「＋」ボタンをタップして、追跡したいイベントを追加しましょう。\n名前を入力し、カテゴリーアイコンを選んで、日時を設定するだけ！',
    icon: 'i-lucide-plus-circle',
    emoji: '➕',
  },
  {
    title: '経過時間をひと目で確認',
    description:
      'メイン画面では、各イベントの経過時間がリアルタイムで表示されます。\n色で経過度合いがわかるので、やり忘れにすぐ気づけます。',
    icon: 'i-lucide-clock',
    emoji: '⏱️',
  },
  {
    title: '履歴を記録・管理',
    description:
      'イベントカードをタップすると、過去の実行履歴を確認できます。\n統計情報やメモも記録でき、振り返りに便利です。',
    icon: 'i-lucide-history',
    emoji: '📋',
  },
  {
    title: '検索・フィルターで絞り込み',
    description:
      'イベントが増えても大丈夫。検索バーやサイドメニューのフィルター機能で、必要なイベントをすぐに見つけられます。',
    icon: 'i-lucide-search',
    emoji: '🔍',
  },
  {
    title: 'データをエクスポート',
    description:
      '設定画面から、すべてのイベントと履歴データをCSV形式でエクスポートできます。\nデータのバックアップや分析に活用しましょう。',
    icon: 'i-lucide-download',
    emoji: '💾',
  },
  {
    title: '準備完了！',
    description:
      'さあ、最初のイベントを追加してみましょう！\n日常のあらゆる活動を記録して、生活をもっとスマートに管理できます。',
    icon: 'i-lucide-rocket',
    emoji: '🚀',
  },
];

/**
 * 共有ステート（コンポーネント間で共有）
 */
const _isVisible = ref(false);
const _currentStepIndex = ref(0);

/**
 * チュートリアルの状態管理 composable
 */
export const useTutorial = () => {
  const settingsStore = useSettingsStore();

  /** チュートリアル表示中かどうか */
  const isVisible = _isVisible;

  /** 現在のステップインデックス */
  const currentStepIndex = _currentStepIndex;

  /** 現在のステップ */
  const currentStep = computed(() => TUTORIAL_STEPS[currentStepIndex.value]);

  /** 合計ステップ数 */
  const totalSteps = TUTORIAL_STEPS.length;

  /** 最初のステップかどうか */
  const isFirstStep = computed(() => currentStepIndex.value === 0);

  /** 最後のステップかどうか */
  const isLastStep = computed(() => currentStepIndex.value === totalSteps - 1);

  /** 進捗率 (0-100) */
  const progress = computed(() =>
    Math.round(((currentStepIndex.value + 1) / totalSteps) * 100),
  );

  /**
   * チュートリアルを開始
   */
  const start = () => {
    currentStepIndex.value = 0;
    isVisible.value = true;
  };

  /**
   * 次のステップへ
   */
  const next = () => {
    if (!isLastStep.value) {
      currentStepIndex.value++;
    }
  };

  /**
   * 前のステップへ
   */
  const prev = () => {
    if (!isFirstStep.value) {
      currentStepIndex.value--;
    }
  };

  /**
   * 特定のステップへジャンプ
   */
  const goTo = (index: number) => {
    if (index >= 0 && index < totalSteps) {
      currentStepIndex.value = index;
    }
  };

  /**
   * チュートリアルを完了して閉じる
   * サーバーの showTutorial を false に更新
   */
  const complete = async () => {
    isVisible.value = false;
    currentStepIndex.value = 0;

    // サーバー設定で showTutorial が true なら false に更新
    if (settingsStore.serverSettings.misc.showTutorial) {
      await settingsStore.toggleTutorial();
    }
  };

  /**
   * チュートリアルをスキップして閉じる（完了と同じ処理）
   */
  const skip = async () => {
    await complete();
  };

  /**
   * 初回起動時にチュートリアルを自動表示するかチェック
   */
  const checkAutoShow = async () => {
    // サーバー設定の読み込みを待ってから showTutorial をチェック
    if (settingsStore.serverSettings.misc.showTutorial) {
      start();
    }
  };

  return {
    // State
    isVisible: readonly(isVisible),
    currentStepIndex: readonly(currentStepIndex),
    currentStep,
    totalSteps,
    isFirstStep,
    isLastStep,
    progress,

    // Actions
    start,
    next,
    prev,
    goTo,
    complete,
    skip,
    checkAutoShow,
  };
};
