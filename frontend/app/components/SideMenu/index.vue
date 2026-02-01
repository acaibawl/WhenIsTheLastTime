<template>
  <Teleport to="body">
    <!-- オーバーレイ -->
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 bg-black/50 z-40"
        role="button"
        aria-label="メニューを閉じる"
        tabindex="-1"
        @click="close"
      />
    </Transition>

    <!-- サイドメニュー -->
    <Transition name="slide">
      <nav
        v-if="isOpen"
        ref="menuRef"
        class="fixed top-0 left-0 bottom-0 w-[280px] sm:w-[320px] lg:w-[360px] bg-white dark:bg-gray-800 z-50 overflow-y-auto shadow-lg"
        role="navigation"
        aria-label="フィルターとナビゲーション"
      >
        <!-- ヘッダー -->
        <div class="pt-6 px-5 pb-2">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            最後はいつ？
          </h2>
        </div>

        <!-- 区切り線 -->
        <div class="border-t border-gray-200 dark:border-gray-700" />

        <!-- 時間フィルター -->
        <SideMenuTimeFilterSection />

        <!-- 区切り線 -->
        <div class="border-t border-gray-200 dark:border-gray-700" />

        <!-- カテゴリーフィルター -->
        <SideMenuCategoryFilterSection />

        <!-- 区切り線 -->
        <div class="border-t border-gray-200 dark:border-gray-700" />

        <!-- ナビゲーション -->
        <SideMenuNavigationSection @navigate="close" />
      </nav>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
const props = defineProps<{
  nickname: string;
}>();

const isOpen = defineModel<boolean>({ default: false });

const menuRef = ref<HTMLElement | null>(null);

/**
 * ニックネームを最大20文字に切り詰め
 */
const truncatedNickname = computed(() => {
  if (props.nickname.length > 20) {
    return `${props.nickname.slice(0, 20)}...`;
  }
  return props.nickname;
});

/**
 * メニューを閉じる
 */
const close = () => {
  isOpen.value = false;
};

/**
 * Escキーでメニューを閉じる
 */
const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && isOpen.value) {
    close();
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});

/**
 * メニューが開いたらフォーカスを設定
 */
watch(isOpen, (newValue) => {
  if (newValue && menuRef.value) {
    nextTick(() => {
      menuRef.value?.focus();
    });
  }
});
</script>

<style scoped>
/* スライドアニメーション */
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}

/* フェードアニメーション */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
