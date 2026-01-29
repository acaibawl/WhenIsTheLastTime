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

// 認証コード（6桁の配列）
const code = ref(['', '', '', '', '', '']);

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

  // 最初の入力欄にフォーカス
  nextTick(() => {
    const firstInput = document.querySelector<HTMLInputElement>('input[data-code-index="0"]');
    firstInput?.focus();
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

// コード入力処理
const onCodeInput = (index: number, event: Event) => {
  const input = event.target as HTMLInputElement;
  const value = input.value;

  // 数字のみ許可
  if (value && !/^\d$/.test(value)) {
    input.value = '';
    return;
  }

  code.value[index] = value;

  // 入力があれば次の入力欄に移動
  if (value && index < 5) {
    const nextInput = document.querySelector<HTMLInputElement>(`input[data-code-index="${index + 1}"]`);
    nextInput?.focus();
  }

  // 6桁すべて入力されたら自動送信
  if (code.value.every(c => c !== '')) {
    onSubmit();
  }
};

// キー操作処理
const onKeyDown = (index: number, event: KeyboardEvent) => {
  // Backspace: 現在の入力欄が空なら前の入力欄に移動
  if (event.key === 'Backspace' && !code.value[index] && index > 0) {
    const prevInput = document.querySelector<HTMLInputElement>(`input[data-code-index="${index - 1}"]`);
    prevInput?.focus();
  }
  // 左矢印キー: 前の入力欄に移動
  else if (event.key === 'ArrowLeft' && index > 0) {
    const prevInput = document.querySelector<HTMLInputElement>(`input[data-code-index="${index - 1}"]`);
    prevInput?.focus();
  }
  // 右矢印キー: 次の入力欄に移動
  else if (event.key === 'ArrowRight' && index < 5) {
    const nextInput = document.querySelector<HTMLInputElement>(`input[data-code-index="${index + 1}"]`);
    nextInput?.focus();
  }
};

// ペースト処理
const onPaste = (event: ClipboardEvent) => {
  event.preventDefault();
  const pastedData = event.clipboardData?.getData('text') || '';
  const digits = pastedData.replace(/\D/g, '').slice(0, 6).split('');

  digits.forEach((digit, index) => {
    if (index < 6) {
      code.value[index] = digit;
    }
  });

  // 最後の入力欄にフォーカス
  const lastIndex = Math.min(digits.length, 5);
  const lastInput = document.querySelector<HTMLInputElement>(`input[data-code-index="${lastIndex}"]`);
  lastInput?.focus();

  // 6桁すべて入力されたら自動送信
  if (code.value.every(c => c !== '')) {
    onSubmit();
  }
};

// フォーム送信
const onSubmit = async () => {
  errorMessage.value = '';

  // 6桁すべて入力されているか確認
  if (code.value.some(c => c === '')) {
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
        code: code.value.join(''),
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
    code.value = ['', '', '', '', '', ''];
    const firstInput = document.querySelector<HTMLInputElement>('input[data-code-index="0"]');
    firstInput?.focus();
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
    code.value = ['', '', '', '', '', ''];

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

    // 最初の入力欄にフォーカス
    nextTick(() => {
      const firstInput = document.querySelector<HTMLInputElement>('input[data-code-index="0"]');
      firstInput?.focus();
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
  navigateTo('/register');
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <!-- 戻るボタン -->
      <button
        type="button"
        class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6"
        @click="onBack"
      >
        <UIcon name="i-heroicons-arrow-left" class="mr-2" />
        戻る
      </button>

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
            <div class="flex justify-center gap-2 mb-4">
              <input
                v-for="(digit, index) in code"
                :key="index"
                v-model="code[index]"
                type="text"
                inputmode="numeric"
                maxlength="1"
                :data-code-index="index"
                class="w-12 h-14 text-center text-2xl font-bold border-2 border-gray-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:outline-none transition-colors"
                :disabled="isLoading || expiresIn === 0"
                @input="onCodeInput(index, $event)"
                @keydown="onKeyDown(index, $event)"
                @paste="onPaste"
              >
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
              :disabled="isLoading || code.some(c => c === '') || expiresIn === 0"
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
