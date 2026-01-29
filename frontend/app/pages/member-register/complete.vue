<script setup lang="ts">
// メタ情報
useHead({
  title: '登録完了',
});

// クエリパラメータからニックネームを取得
const route = useRoute();
const nickname = ref(route.query.nickname as string);

// ニックネームがない場合は登録画面にリダイレクト
if (!nickname.value) {
  await navigateTo('/member-register');
}
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
        <ULink
          to="/login"
          as="button"
          class="inline-flex items-center justify-center px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold text-lg rounded-lg shadow-lg hover:shadow-xl transition-all"
        >
          はじめる
        </ULink>
      </div>
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
