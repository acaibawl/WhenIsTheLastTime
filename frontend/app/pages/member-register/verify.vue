<script setup lang="ts">
// レスポンスの型定義
interface VerifyResponse {
  data: {
    user: {
      nickname: string;
    };
  };
}

// メタ情報
useHead({
  title: '認証コード入力',
});

// クエリパラメータからメールアドレスを取得
const route = useRoute();
const email = ref(route.query.email as string || '');

// メールアドレスがない場合は登録画面にリダイレクト
if (!email.value) {
  await navigateTo('/register');
}

// 認証コード（6桁の数字配列）
const code = ref<number[]>([]);

// PinInput コンポーネントへの参照
const pinInputRef = ref();

// エラーメッセージ
const errorMessage = ref('');

// ローディング状態
const isLoading = ref(false);

// 有効期限（10分 = 600秒）
const expiresIn = ref(600);
const expiresAt = ref(new Date(Date.now() + expiresIn.value * 1000));

// 再送信のクールダウン（60秒）
const resendCooldown = ref(0);
const canResend = computed(() => resendCooldown.value === 0);

// タイマー処理
let expiresTimer: ReturnType<typeof setInterval> | null = null;
let resendTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  // 有効期限のカウントダウン
  expiresTimer = setInterval(() => {
    expiresIn.value = Math.max(0, Math.floor((expiresAt.value.getTime() - Date.now()) / 1000));
    if (expiresIn.value === 0) {
      errorMessage.value = '認証コードの有効期限が切れました。再送信してください';
      if (expiresTimer) clearInterval(expiresTimer);
    }
  }, 1000);

  // 認証コード入力欄にフォーカス
  nextTick(() => {
    pinInputRef.value?.$el?.querySelector('input')?.focus();
  });
});

onUnmounted(() => {
  if (expiresTimer) clearInterval(expiresTimer);
  if (resendTimer) clearInterval(resendTimer);
});

// 有効期限の表示（MM:SS形式）
const expiresDisplay = computed(() => {
  const minutes = Math.floor(expiresIn.value / 60);
  const seconds = expiresIn.value % 60;
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

// 6桁入力完了時の処理
const onComplete = () => {
  // コードの値を文字列に変換
  const codeValue = code.value.join('');

  if (codeValue.length === 6 && /^\d{6}$/.test(codeValue)) {
    onSubmit();
  }
};

// フォーム送信
const onSubmit = async () => {
  errorMessage.value = '';

  // コードの値を文字列に変換
  const codeValue = code.value.join('');

  // 6桁すべて入力されているか確認
  if (codeValue.length !== 6 || !/^\d{6}$/.test(codeValue)) {
    errorMessage.value = '6桁の数字を入力してください';
    return;
  }

  isLoading.value = true;

  const config = useRuntimeConfig();

  try {
    const response = await $fetch<VerifyResponse>('/auth/register/verify', {
      method: 'POST',
      baseURL: config.public.apiBaseUrl,
      body: {
        email: email.value,
        code: codeValue,
      },
    });

    // 成功: 登録完了画面に遷移
    await navigateTo({
      path: '/member-register/complete',
      query: {
        nickname: response.data?.user?.nickname || '',
      },
    });
  } catch (error: any) {
    console.error('Verification error:', error);

    if (error?.data?.error) {
      const apiError = error.data.error;

      switch (apiError.code) {
        case 'INVALID_VERIFICATION_CODE':
          errorMessage.value = '認証コードが正しくありません';
          break;
        case 'VERIFICATION_CODE_EXPIRED':
          errorMessage.value = '認証コードの有効期限が切れました。再送信してください';
          break;
        case 'TOO_MANY_ATTEMPTS':
          errorMessage.value = '試行回数が上限に達しました。しばらくしてからお試しください';
          break;
        default:
          errorMessage.value = apiError.message || '認証処理中にエラーが発生しました';
      }
    } else {
      errorMessage.value = 'ネットワークエラーが発生しました。もう一度お試しください';
    }

    // エラー時はコードをクリア
    code.value = [];
  } finally {
    isLoading.value = false;
  }
};

// 認証コード再送信
const onResend = async () => {
  if (!canResend.value) return;

  errorMessage.value = '';
  isLoading.value = true;

  const config = useRuntimeConfig();

  try {
    await $fetch('/auth/register/resend-code', {
      method: 'POST',
      baseURL: config.public.apiBaseUrl,
      body: {
        email: email.value,
      },
    });

    // 有効期限をリセット
    expiresIn.value = 600;
    expiresAt.value = new Date(Date.now() + 600000);

    // コードをクリア
    code.value = [];

    // 再送信クールダウンを開始
    resendCooldown.value = 60;
    resendTimer = setInterval(() => {
      resendCooldown.value = Math.max(0, resendCooldown.value - 1);
      if (resendCooldown.value === 0 && resendTimer) {
        clearInterval(resendTimer);
      }
    }, 1000);

    // 成功メッセージ（トースト表示）
    useToast().add({
      title: '認証コードを再送信しました',
      color: 'info',
    });

    // 認証コード入力欄にフォーカス
    nextTick(() => {
      pinInputRef.value?.$el?.querySelector('input')?.focus();
    });
  } catch (error: any) {
    console.error('Resend error:', error);

    if (error?.data?.error) {
      const apiError = error.data.error;

      switch (apiError.code) {
        case 'REGISTRATION_NOT_FOUND':
          errorMessage.value = '登録情報が見つかりません。最初からやり直してください';
          setTimeout(() => navigateTo('/register'), 2000);
          break;
        case 'RESEND_COOLDOWN':
          errorMessage.value = `再送信は${apiError.details?.retryAfter || 60}秒後に可能です`;
          break;
        case 'RATE_LIMIT_EXCEEDED':
          errorMessage.value = '送信回数の上限に達しました。しばらくしてからお試しください';
          break;
        default:
          errorMessage.value = apiError.message || '再送信処理中にエラーが発生しました';
      }
    } else {
      errorMessage.value = 'ネットワークエラーが発生しました。もう一度お試しください';
    }
  } finally {
    isLoading.value = false;
  }
};

// 戻るボタン
const onBack = () => {
  navigateTo('/member-register');
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <!-- 戻るボタン -->
      <ULink
        to="/member-register"
        as="button"
        class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6"
      >
        <UIcon name="i-heroicons-arrow-left" class="mr-2" />
        戻る
      </ULink>

      <!-- アイコンとタイトル -->
      <div class="text-center">
        <div class="text-5xl mb-4">
          ✉️
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-4">
          認証コードを入力
        </h1>
        <p class="text-gray-600">
          <span class="font-medium">{{ email }}</span> に<br>
          6桁の認証コードを送信しました
        </p>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <!-- エラーメッセージ -->
        <UAlert
          v-if="errorMessage"
          color="error"
          variant="soft"
          :title="errorMessage"
          class="mb-6"
          :close-button="{ icon: 'i-heroicons-x-mark-20-solid', color: 'red', variant: 'link' }"
          @close="errorMessage = ''"
        />

        <form @submit.prevent="onSubmit" class="space-y-6">
          <!-- 認証コード入力 -->
          <div>
            <div class="flex justify-center mb-4">
              <UPinInput
                ref="pinInputRef"
                v-model="code"
                :length="6"
                placeholder="0"
                type="number"
                :disabled="isLoading || expiresIn === 0"
                size="xl"
                @complete="onComplete"
                otp
              />
            </div>

            <!-- 有効期限表示 -->
            <div class="text-center">
              <p
                class="text-sm"
                :class="expiresIn < 60 ? 'text-red-600 font-medium' : 'text-gray-600'"
              >
                有効期限: {{ expiresDisplay }}
              </p>
            </div>
          </div>

          <!-- 確認ボタン -->
          <div>
            <UButton
              type="submit"
              block
              size="lg"
              :loading="isLoading"
              :disabled="isLoading || code.values.length !== 6 || expiresIn === 0"
            >
              確認する
            </UButton>
          </div>
        </form>

        <!-- 再送信リンク -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600 mb-2">
            コードが届きませんか？
          </p>
          <button
            type="button"
            class="font-medium text-primary-600 hover:text-primary-500 disabled:text-gray-400 disabled:cursor-not-allowed"
            :disabled="!canResend || isLoading"
            @click="onResend"
          >
            <span v-if="canResend">再送信する</span>
            <span v-else>再送信可能まで {{ resendCooldown }}秒</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
