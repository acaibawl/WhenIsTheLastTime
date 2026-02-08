<template>
  <Teleport to="body">
    <Transition name="tutorial-fade">
      <div
        v-if="isVisible"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="チュートリアル"
      >
        <!-- 背景オーバーレイ -->
        <div
          class="absolute inset-0 bg-black/60 backdrop-blur-sm"
          @click="handleSkip"
        />

        <!-- チュートリアルカード -->
        <div
          class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden"
        >
          <!-- プログレスバー -->
          <div class="h-1 bg-gray-200 dark:bg-gray-700">
            <div
              class="h-full bg-blue-500 transition-all duration-500 ease-out"
              :style="{ width: `${progress}%` }"
            />
          </div>

          <!-- スキップボタン -->
          <button
            v-if="!isLastStep"
            type="button"
            class="absolute top-4 right-4 text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors z-10"
            @click="handleSkip"
          >
            スキップ
          </button>

          <!-- コンテンツ領域 -->
          <div class="px-6 pt-8 pb-6">
            <!-- アイコン/イラスト -->
            <div class="flex justify-center mb-6">
              <div
                class="w-24 h-24 rounded-full flex items-center justify-center text-5xl"
                :class="stepBgClass"
              >
                <Transition name="tutorial-step" mode="out-in">
                  <span :key="currentStepIndex">{{ currentStep?.emoji }}</span>
                </Transition>
              </div>
            </div>

            <!-- タイトル -->
            <Transition name="tutorial-step" mode="out-in">
              <h2
                :key="`title-${currentStepIndex}`"
                class="text-xl font-bold text-gray-900 dark:text-white text-center mb-3"
              >
                {{ currentStep?.title }}
              </h2>
            </Transition>

            <!-- 説明文 -->
            <Transition name="tutorial-step" mode="out-in">
              <p
                :key="`desc-${currentStepIndex}`"
                class="text-gray-600 dark:text-gray-300 text-center leading-relaxed whitespace-pre-line min-h-[80px]"
              >
                {{ currentStep?.description }}
              </p>
            </Transition>

            <!-- ステップインジケーター -->
            <div class="flex justify-center gap-2 mt-6 mb-6">
              <button
                v-for="(_, index) in totalSteps"
                :key="index"
                type="button"
                class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                :class="[
                  index === currentStepIndex
                    ? 'bg-blue-500 scale-125'
                    : index < currentStepIndex
                      ? 'bg-blue-300 dark:bg-blue-600'
                      : 'bg-gray-300 dark:bg-gray-600',
                ]"
                :aria-label="`ステップ ${index + 1} に移動`"
                @click="goTo(index)"
              />
            </div>

            <!-- ナビゲーションボタン -->
            <div class="flex gap-3">
              <!-- 戻るボタン -->
              <UButton
                v-if="!isFirstStep"
                variant="outline"
                color="neutral"
                class="flex-1"
                size="lg"
                @click="prev"
              >
                <UIcon name="i-lucide-arrow-left" class="w-4 h-4 mr-1" />
                戻る
              </UButton>

              <!-- 次へ/はじめるボタン -->
              <UButton
                color="primary"
                class="flex-1"
                size="lg"
                @click="handleNext"
              >
                <template v-if="isLastStep">
                  はじめる
                  <UIcon name="i-lucide-rocket" class="w-4 h-4 ml-1" />
                </template>
                <template v-else>
                  次へ
                  <UIcon name="i-lucide-arrow-right" class="w-4 h-4 ml-1" />
                </template>
              </UButton>
            </div>
          </div>

          <!-- ステップカウンター -->
          <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-3 text-center">
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ currentStepIndex + 1 }} / {{ totalSteps }}
            </span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { useTutorial } from '~/composables/useTutorial';

const {
  isVisible,
  currentStepIndex,
  currentStep,
  totalSteps,
  isFirstStep,
  isLastStep,
  progress,
  next,
  prev,
  goTo,
  complete,
  skip,
} = useTutorial();

/**
 * ステップ背景色クラス
 */
const stepBgColors = [
  'bg-blue-100 dark:bg-blue-900/40',
  'bg-green-100 dark:bg-green-900/40',
  'bg-yellow-100 dark:bg-yellow-900/40',
  'bg-purple-100 dark:bg-purple-900/40',
  'bg-indigo-100 dark:bg-indigo-900/40',
  'bg-cyan-100 dark:bg-cyan-900/40',
  'bg-orange-100 dark:bg-orange-900/40',
];

const stepBgClass = computed(() => {
  return stepBgColors[currentStepIndex.value % stepBgColors.length];
});

/**
 * 次へボタンのハンドラ
 */
const handleNext = () => {
  if (isLastStep.value) {
    complete();
  } else {
    next();
  }
};

/**
 * スキップボタンのハンドラ
 */
const handleSkip = () => {
  skip();
};

// 外部から呼び出せるように公開
defineExpose({
  start: () => {
    const tutorial = useTutorial();
    tutorial.start();
  },
});
</script>

<style scoped>
/* フェードイン/アウト */
.tutorial-fade-enter-active,
.tutorial-fade-leave-active {
  transition: opacity 0.3s ease;
}

.tutorial-fade-enter-from,
.tutorial-fade-leave-to {
  opacity: 0;
}

/* ステップ切り替えアニメーション */
.tutorial-step-enter-active {
  transition: all 0.3s ease-out;
}

.tutorial-step-leave-active {
  transition: all 0.2s ease-in;
}

.tutorial-step-enter-from {
  opacity: 0;
  transform: translateX(20px);
}

.tutorial-step-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>
