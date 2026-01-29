<template>
<div>
    <UModal
        v-model:open="isOpen"
        :fullscreen="isFullscreen"
        title="新しいイベント"
        :close="{ onClick: handleCancel }"
    >
        <template #body="">
            <div class="space-y-6">
                <!-- エラーメッセージ（全般） -->
                <UAlert
                v-if="errors.general"
                color="error"
                icon="i-lucide-alert-circle"
                :title="errors.general"
                :close-button="{ icon: 'i-lucide-x', color: 'neutral', variant: 'link' }"
                @close="errors.general = undefined"
                />

                <form @submit.prevent="handleSave">
                <!-- イベント名 -->
                <UFormGroup
                    label="イベント名"
                    :error="errors.eventName"
                    required
                    class="mb-6"
                >
                    <UInput
                    v-model="eventName"
                    placeholder="重要なイベントをここに入力する"
                    maxlength="100"
                    size="lg"
                    :ui="{ base: 'w-full' }"
                    aria-required="true"
                    :aria-invalid="!!errors.eventName"
                    />
                    <template #hint>
                    <span class="text-xs text-gray-500">
                        {{ eventName.length }} / 100
                    </span>
                    </template>
                </UFormGroup>

                <!-- カテゴリーアイコン -->
                <div class="mb-6">
                    <CategoryIconSelector v-model="selectedIcon" />
                </div>

                <!-- 初回実行日時 -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    初回実行日時
                    <span class="text-red-500">*</span>
                    </label>

                    <div class="space-y-3">
                    <!-- 日付ピッカー -->
                    <UFormGroup label="日付">
                        <UInput
                        v-model="selectedDate"
                        type="date"
                        size="lg"
                        icon="i-lucide-calendar"
                        :max="maxDate"
                        aria-required="true"
                        />
                    </UFormGroup>

                    <!-- 時刻ピッカー -->
                    <UFormGroup label="時刻">
                        <UInput
                        v-model="selectedTime"
                        type="time"
                        size="lg"
                        icon="i-lucide-clock"
                        aria-required="true"
                        />
                    </UFormGroup>
                    </div>
                </div>

                <!-- メモ（任意） -->
                <UFormGroup
                    label="メモ（任意）"
                    class="mb-6"
                >
                    <UTextarea
                    v-model="memo"
                    placeholder="メモを入力..."
                    :rows="4"
                    maxlength="500"
                    :ui="{ base: 'w-full' }"
                    />
                    <template #hint>
                    <span class="text-xs text-gray-500">
                        {{ memo.length }} / 500
                    </span>
                    </template>
                </UFormGroup>
                </form>
            </div>
        </template>

        <template #footer="">
            <div class="flex justify-end gap-3">
                <UButton
                color="neutral"
                variant="outline"
                @click="handleCancel"
                >
                キャンセル
                </UButton>
                <UButton
                :disabled="!isValid || isSaving"
                :loading="isSaving"
                color="primary"
                @click="handleSave"
                >
                完了
                </UButton>
            </div>
        </template>
    </UModal>

    <!-- キャンセル確認ダイアログ -->
    <UModal v-model:open="showCancelDialog">
        <UCard>
        <template #header>
            <h3 class="text-lg font-semibold">
            変更を破棄しますか？
            </h3>
        </template>

        <p class="text-gray-600 dark:text-gray-400">
            入力した内容は保存されません。
        </p>

        <template #footer>
            <div class="flex justify-end gap-3">
            <UButton
                color="neutral"
                variant="ghost"
                @click="showCancelDialog = false"
            >
                キャンセル
            </UButton>
            <UButton
                color="error"
                @click="handleConfirmCancel"
            >
                破棄
            </UButton>
            </div>
        </template>
        </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import CategoryIconSelector from './CategoryIconSelector.vue';

// Props & Emits
interface Props {
  modelValue: boolean;
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void;
  (e: 'created'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Composable
const {
  eventName,
  selectedIcon,
  selectedDate,
  selectedTime,
  memo,
  isSaving,
  errors,
  isValid,
  hasChanges,
  save,
  reset,
} = useEventForm();

// ローカルステート
const showCancelDialog = ref(false);

// 内部のモーダル開閉状態
const isOpen = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

// 最大日付（今日まで）
const maxDate = computed(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
});

// レスポンシブ: モバイルではフルスクリーン
const isFullscreen = computed(() => {
  // SSRでは常にfalse、クライアントサイドで画面幅を判定
  if (import.meta.client) {
    return window.innerWidth < 640;
  }
  return false;
});

// メソッド
const handleSave = async () => {
  const success = await save();
  if (success) {
    emit('created');
    emit('update:modelValue', false);
    reset();
  }
};

const handleCancel = () => {
  if (hasChanges.value) {
    showCancelDialog.value = true;
  } else {
    emit('update:modelValue', false);
    reset();
  }
};

const handleConfirmCancel = () => {
  showCancelDialog.value = false;
  emit('update:modelValue', false);
  reset();
};

// モーダルが開かれたときにフォームをリセット
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    reset();
  }
});
</script>
