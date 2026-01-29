<script setup lang="ts">
// メタ情報
useHead({
  title: '登録完了',
});

// クエリパラメータからニックネームを取得
const route = useRoute();
const nickname = ref(route.query.nickname as string || 'ゲスト');

// ニックネームがない場合は登録画面にリダイレクト
if (!nickname.value || nickname.value === 'ゲスト') {
  await navigateTo('/register');
}

// 自動的にメイン画面に遷移（3秒後）
let redirectTimer: NodeJS.Timeout | null = null;

onMounted(() => {
  redirectTimer = setTimeout(() => {
    navigateTo('/');
  }, 3000);
});

onUnmounted(() => {
  if (redirectTimer) {
    clearTimeout(redirectTimer);
  }
});

// 手動でメイン画面に遷移
const onStart = () => {
  if (redirectTimer) {
    clearTimeout(redirectTimer);
  }
  navigateTo('/');
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
      <!-- 成功アイコン -->
      <div class="mb-8">
        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100">
          <UIcon name="i-heroicons-check" class="h-16 w-16 text-green-600" />
        </div>
      </div>

      <!-- タイトル -->
      <h1 class="text-3xl font-bold text-gray-900 mb-4">
        登録が完了しました！
      </h1>

      <!-- ウェルカムメッセージ -->
      <p class="text-xl text-gray-700 mb-8">
        ようこそ、<span class="font-bold text-primary-600">{{ nickname }}</span> さん
      </p>

      <!-- 説明文 -->
      <p class="text-gray-600 mb-8">
        アカウントの登録が完了しました。<br>
        日常のイベントを記録して、最後の実行からの経過時間を追跡しましょう。
      </p>

      <!-- はじめるボタン -->
      <div class="mt-8">
        <UButton
          size="xl"
          @click="onStart"
        >
          はじめる
        </UButton>
      </div>

      <!-- 自動遷移の案内 -->
      <p class="mt-6 text-sm text-gray-500">
        3秒後に自動的にメイン画面に移動します
      </p>
    </div>
  </div>
</template>

<style scoped>
/* アニメーション効果 */
.min-h-screen > div {
  animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
