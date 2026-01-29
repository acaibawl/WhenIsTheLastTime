<script setup lang="ts">
import { useForm } from 'vee-validate';
import * as yup from 'yup';

// メタ情報
useHead({
  title: '新規アカウント登録',
});

// パスワード表示/非表示
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

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
    .matches(/(?=.*[A-Za-z])(?=.*\d)/, 'パスワードは英字と数字を含めてください'),
  passwordConfirmation: yup
    .string()
    .required()
    .oneOf([yup.ref('password')], 'パスワードが一致しません'),
  nickname: yup
    .string()
    .required()
    .min(1)
    .max(10),
});

// vee-validateフォーム設定
const { handleSubmit, errors, setErrors, defineField } = useForm({
  validationSchema: schema,
  initialValues: {
    email: '',
    password: '',
    passwordConfirmation: '',
    nickname: '',
  },
});

// フィールド定義（リアルタイムバリデーション有効化）
const [email] = defineField('email', { validateOnModelUpdate: true });
const [password] = defineField('password', { validateOnModelUpdate: true });
const [passwordConfirmation] = defineField('passwordConfirmation', { validateOnModelUpdate: true });
const [nickname] = defineField('nickname', { validateOnModelUpdate: true });

// フォーム送信
const onSubmit = handleSubmit(async (values) => {
  isLoading.value = true;
  generalError.value = '';

  const config = useRuntimeConfig();

  try {
    const response = await $fetch('/auth/register/send-code', {
      method: 'POST',
      baseURL: config.public.apiBaseUrl,
      body: {
        email: values.email,
        password: values.password,
        nickname: values.nickname,
      },
    });

    // 成功: 認証コード入力画面に遷移
    await navigateTo({
      path: '/member-register/verify',
      query: {
        email: values.email,
      },
    });
  } catch (error: any) {
    console.error('Registration error:', error);

    if (error?.data?.error) {
      const apiError = error.data.error;

      // バリデーションエラー
      if (apiError.code === 'VALIDATION_ERROR' && apiError.details) {
        setErrors(apiError.details);
      }
      // レート制限エラー
      else if (apiError.code === 'RATE_LIMIT_EXCEEDED') {
        generalError.value = apiError.message || '送信回数の上限に達しました。しばらくしてからお試しください';
      }
      // その他のエラー
      else {
        generalError.value = apiError.message || '登録処理中にエラーが発生しました';
      }
    } else {
      generalError.value = 'ネットワークエラーが発生しました。もう一度お試しください';
    }
  } finally {
    isLoading.value = false;
  }
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <!-- 戻るボタン -->
      <NuxtLink
        to="/"
        class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6"
      >
        <UIcon name="i-heroicons-arrow-left" class="mr-2" />
        戻る
      </NuxtLink>

      <!-- ロゴとタイトル -->
      <div class="text-center">
        <div class="text-5xl mb-4">
          🕐
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
          最後はいつ？
        </h1>
        <h2 class="text-xl text-gray-700">
          新規アカウント登録
        </h2>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form @submit="onSubmit" class="space-y-6">
          <!-- 全体エラーメッセージ -->
            <UAlert
            v-if="generalError"
              color="error"
              variant="soft"
              :title="generalError"
              :close-button="{ icon: 'i-heroicons-x-mark-20-solid', color: 'red', variant: 'link' }"
              @close="generalError = ''"
            />

          <!-- メールアドレス -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              メールアドレス<span class="text-red-500 ml-1">*</span>
            </label>
            <UInput
              id="email"
              v-model="email"
              type="email"
              placeholder="example@email.com"
              size="lg"
              :disabled="isLoading"
            />
            <p v-if="errors.email" class="mt-2 text-sm text-red-600">
                {{ errors.email }}
              </p>
          </div>

          <!-- パスワード -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              パスワード<span class="text-red-500 ml-1">*</span>
            </label>
            <UInput
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="8文字以上、英数字を含む"
              size="lg"
              :disabled="isLoading"
            >
              <template #trailing>
                <UButton
                  color="secondary"
                  variant="link"
                  :icon="showPassword ? 'i-heroicons-eye-slash' : 'i-heroicons-eye'"
                  :padded="false"
                  @click="showPassword = !showPassword"
                />
              </template>
            </UInput>
            <p class="mt-1 text-sm text-gray-500">※8文字以上、英数字を含む</p>
            <p v-if="errors.password" class="mt-2 text-sm text-red-600">
                {{ errors.password }}
              </p>
          </div>

          <!-- パスワード（確認） -->
          <div>
            <label for="passwordConfirmation" class="block text-sm font-medium text-gray-700 mb-2">
              パスワード（確認）<span class="text-red-500 ml-1">*</span>
            </label>
            <UInput
              id="passwordConfirmation"
              v-model="passwordConfirmation"
              :type="showPasswordConfirmation ? 'text' : 'password'"
              placeholder="パスワードを再入力"
              size="lg"
              :disabled="isLoading"
            >
              <template #trailing>
                <UButton
                  color="secondary"
                  variant="link"
                  :icon="showPasswordConfirmation ? 'i-heroicons-eye-slash' : 'i-heroicons-eye'"
                  :padded="false"
                  @click="showPasswordConfirmation = !showPasswordConfirmation"
                />
              </template>
            </UInput>
            <p v-if="errors.passwordConfirmation" class="mt-2 text-sm text-red-600">
                {{ errors.passwordConfirmation }}
              </p>
          </div>

          <!-- ニックネーム -->
          <div>
            <label for="nickname" class="block text-sm font-medium text-gray-700 mb-2">
              ニックネーム<span class="text-red-500 ml-1">*</span>
            </label>
            <UInput
              id="nickname"
              v-model="nickname"
              type="text"
              placeholder="Taro"
              size="lg"
              :disabled="isLoading"
            />
            <p class="mt-1 text-sm text-gray-500">※1〜10文字</p>
            <p v-if="errors.nickname" class="mt-2 text-sm text-red-600">
                {{ errors.nickname }}
            </p>
          </div>

          <!-- 送信ボタン -->
          <div>
            <UButton
              type="submit"
              block
              size="lg"
              :loading="isLoading"
              :disabled="isLoading"
            >
              認証コードを送信
            </UButton>
          </div>
        </form>

        <!-- ログインリンク -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            既にアカウントをお持ちですか？
          </p>
          <NuxtLink
            to="/login"
            class="font-medium text-primary-600 hover:text-primary-500"
          >
            ログインはこちら
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>
