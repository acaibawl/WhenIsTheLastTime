<script setup lang="ts">
import { useForm } from 'vee-validate';
import * as yup from 'yup';

// メタ情報
useHead({
  title: 'ログイン',
});

// パスワード表示/非表示
const showPassword = ref(false);

// 全体エラーメッセージ
const generalError = ref('');

// ローディング状態
const isLoading = ref(false);

// バリデーションスキーマ
const schema = yup.object({
  email: yup
    .string()
    .required()
    .email()
    .max(255),
  password: yup
    .string()
    .required()
    .min(8)
    .max(32),
});

// vee-validateフォーム設定
const { handleSubmit, errors, setErrors, defineField } = useForm({
  validationSchema: schema,
  initialValues: {
    email: '',
    password: '',
  },
});

// フィールド定義（リアルタイムバリデーション有効化）
const [email] = defineField('email', { validateOnModelUpdate: true });
const [password] = defineField('password', { validateOnModelUpdate: true });

// フォーム送信
const onSubmit = handleSubmit(async (values) => {
  isLoading.value = true;
  generalError.value = '';

  const config = useRuntimeConfig();
  const accessTokenCookie = useCookie('access_token', {
    maxAge: 60 * 60 * 24 * 30, // 30日間
    path: '/',
    sameSite: 'lax',
    secure: config.public.baseUrl.startsWith('https'),
    httpOnly: true,
  });

  try {
    const response: any = await $fetch('/auth/login', {
      method: 'POST',
      baseURL: config.public.apiBaseUrl,
      body: {
        email: values.email,
        password: values.password,
      },
    });

    // トークンをCookieに保存
    if (response?.data?.accessToken) {
      accessTokenCookie.value = response.data.accessToken;
    }

    // ログイン成功: ホーム画面に遷移
    await navigateTo('/');
  } catch (error: any) {
    console.error('Login error:', error);

    if (error?.data?.error) {
      const apiError = error.data.error;

      // バリデーションエラー
      if (apiError.code === 'VALIDATION_ERROR' && apiError.details) {
        setErrors(apiError.details);
      } else if (apiError.code === 'AUTHENTICATION_ERROR') {
        // 認証エラー
        generalError.value = apiError.message || 'メールアドレスまたはパスワードが正しくありません';
      } else {
        // その他のエラー
        generalError.value = apiError.message || 'ログイン処理中にエラーが発生しました';
      }
    } else {
      generalError.value = 'サーバーとの通信に失敗しました';
    }
  } finally {
    isLoading.value = false;
  }
});
</script>

<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-2xl">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold">
          ログイン
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          アカウントにログインしてください
        </p>
      </div>

      <UCard>
        <form class="space-y-6" @submit.prevent="onSubmit">
          <!-- 全体エラーメッセージ -->
          <UAlert
            v-if="generalError"
            color="error"
            variant="soft"
            :title="generalError"
            icon="i-lucide-alert-circle"
          />

          <!-- メールアドレス -->
          <UFormField
            label="メールアドレス"
            :error="errors.email"
            required
          >
            <UInput
              v-model="email"
              type="email"
              placeholder="example@example.com"
              autocomplete="email"
              :disabled="isLoading"
              size="xl"
              :error="!!errors.email"
              class="w-full"
            />
          </UFormField>

          <!-- パスワード -->
          <UFormField
            label="パスワード"
            :error="errors.password"
            required
          >
            <UInput
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="8文字以上32文字以内"
              autocomplete="current-password"
              :disabled="isLoading"
              size="xl"
              :error="!!errors.password"
              class="w-full"
            >
              <template #trailing>
                <UButton
                  color="neutral"
                  variant="link"
                  :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                  :padded="false"
                  type="button"
                  :disabled="isLoading"
                  @click="showPassword = !showPassword"
                />
              </template>
            </UInput>
          </UFormField>

          <!-- ログインボタン -->
          <UButton
            type="submit"
            block
            size="xl"
            :loading="isLoading"
            :disabled="isLoading"
          >
            ログイン
          </UButton>
        </form>

        <!-- 区切り線 -->
        <div class="relative my-8">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300 dark:border-gray-700" />
          </div>
          <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white dark:bg-gray-900 text-gray-500">
              まだアカウントをお持ちでない方
            </span>
          </div>
        </div>

        <!-- 会員登録へのリンク -->
        <UButton
          to="/member-register"
          color="neutral"
          variant="outline"
          block
          size="xl"
          :disabled="isLoading"
        >
          新規アカウント登録
        </UButton>
      </UCard>
    </div>
  </div>
</template>
