<template>
  <div>
    <UModal
      v-model:open="isOpen"
      :fullscreen="isFullscreen"
      title="イベントを編集"
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
                placeholder="イベント名を入力"
                maxlength="100"
                size="lg"
                class="w-full"
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
            :disabled="!isValid || !hasChanges || isSaving"
            :loading="isSaving"
            color="primary"
            @click="handleSave"
          >
            更新
          </UButton>
        </div>
      </template>
    </UModal>

    <!-- キャンセル確認ダイアログ -->
    <UModal
      v-model:open="showCancelDialog"
      title="変更を破棄しますか？"
      :ui="{ footer: 'justify-end' }"
    >
      <template #body>
        <p class="text-gray-600 dark:text-gray-400">
          入力した内容は保存されません。
        </p>
      </template>

      <template #footer>
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
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import CategoryIconSelector from './CategoryIconSelector.vue';
import type { Event } from '~/types/eventHistory';

// Props & Emits
interface Props {
  modelValue: boolean;
  event: Event | null;
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void;
  (e: 'updated', event: Event): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Composable
const {
  eventName,
  selectedIcon,
  isSaving,
  errors,
  isValid,
  hasChanges,
  save,
  reset,
  initializeWithEvent,
} = useEventEditForm(props.event?.id ?? 0);

// ローカルステート
const showCancelDialog = ref(false);

// 内部のモーダル開閉状態
const isOpen = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
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
  const updatedEvent = await save();
  if (updatedEvent) {
    emit('updated', updatedEvent);
    emit('update:modelValue', false);
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

// モーダルが開かれたときにイベントデータでフォームを初期化
// イベントが変更されたときにフォームを再初期化
watchEffect(() => {
  if (props.modelValue && props.event) {
    initializeWithEvent(props.event);
  }
});
</script>
