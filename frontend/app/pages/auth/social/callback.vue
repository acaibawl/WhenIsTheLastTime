<script setup lang="ts">
import { useAuthUserStore } from '~/stores/authUser';

// メタ情報
useHead({
  title: 'ソーシャル認証中...',
});

const authStore = useAuthUserStore();
const route = useRoute();
const error = ref('');
const isProcessing = ref(true);

onMounted(async () => {
  try {
    const token = route.query.token as string | undefined;
    const provider = route.query.provider as string | undefined;

    if (!token || !provider) {
      error.value = '認証情報が不正です。もう一度お試しください。';
      isProcessing.value = false;
      return;
    }

    // JWTトークンをCookieに保存してログイン状態にする
    const config = useRuntimeConfig();
    const accessTokenCookie = useCookie('access_token', {
      maxAge: 60 * 60 * 24 * 30, // 30日間
      path: '/',
      sameSite: 'lax',
      secure: config.public.baseUrl.startsWith('https'),
    });
    accessTokenCookie.value = token;
    authStore.loggedIn();

    // ホーム画面にリダイレクト
    await navigateTo('/', { replace: true });
  } catch (e: any) {
    console.error('Social auth callback error:', e);
    error.value = '認証処理中にエラーが発生しました。もう一度お試しください。';
    isProcessing.value = false;
  }
});
</script>

<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md text-center">
      <!-- 処理中 -->
      <div v-if="isProcessing && !error">
        <div class="text-5xl mb-4">
          🔄
        </div>
        <h1 class="text-2xl font-bold mb-4">
          認証処理中...
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          しばらくお待ちください
        </p>
      </div>

      <!-- エラー -->
      <div v-if="error">
        <div class="text-5xl mb-4">
          ⚠️
        </div>
        <h1 class="text-2xl font-bold mb-4 text-red-600">
          認証エラー
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
          {{ error }}
        </p>
        <UButton
          to="/login"
          size="xl"
        >
          ログイン画面に戻る
        </UButton>
      </div>
    </div>
  </div>
</template>
