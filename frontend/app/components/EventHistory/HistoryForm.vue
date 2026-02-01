<template>
  <div class="space-y-4">
    <!-- 日付入力 -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        <span class="required">日付</span>
      </label>
      <input
        v-model="formDate"
        type="date"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        required
      >
    </div>

    <!-- 時刻入力 -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        <span class="required">時刻</span>
      </label>
      <input
        v-model="formTime"
        type="time"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        required
      >
    </div>

    <!-- メモ入力 -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        メモ
      </label>
      <textarea
        v-model="formMemo"
        rows="3"
        maxlength="500"
        placeholder="メモを入力（任意）"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
      />
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">
        {{ formMemo.length }} / 500
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { format } from 'date-fns';
import type { History } from '~/types/eventHistory';

export interface HistoryFormData {
  date: string;
  time: string;
  memo: string;
}

const props = defineProps<{
  /** 編集対象の履歴（編集モード時に指定、追加モード時はundefined） */
  history?: History;
}>();

const emit = defineEmits<{
  /** フォームの値が変更されたときに発火 */
  'update:formData': [data: HistoryFormData];
}>();

// フォームの状態
const formDate = ref('');
const formTime = ref('');
const formMemo = ref('');

// 現在日時をデフォルト値として設定
const setDefaultDateTime = () => {
  const now = new Date();
  formDate.value = format(now, 'yyyy-MM-dd');
  formTime.value = format(now, 'HH:mm');
  formMemo.value = '';
};

// 編集対象の履歴データをフォームに反映
const setHistoryData = (history: History) => {
  const executedDate = new Date(history.executedAt);
  formDate.value = format(executedDate, 'yyyy-MM-dd');
  formTime.value = format(executedDate, 'HH:mm');
  formMemo.value = history.memo || '';
};

// 初期化処理
const initializeForm = () => {
  if (props.history) {
    setHistoryData(props.history);
  } else {
    setDefaultDateTime();
  }
};

// props.historyが変更されたときにフォームを再初期化
watch(() => props.history, (newHistory) => {
  if (newHistory) {
    setHistoryData(newHistory);
  } else {
    setDefaultDateTime();
  }
}, { immediate: true });

// フォームの値が変更されたらemit
watch([formDate, formTime, formMemo], () => {
  emit('update:formData', {
    date: formDate.value,
    time: formTime.value,
    memo: formMemo.value,
  });
}, { immediate: true });

// フォームをリセットする関数を公開
const reset = () => {
  initializeForm();
};

defineExpose({
  reset,
});
</script>
