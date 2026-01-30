<script setup lang="ts">
interface HealthResponse {
  success: boolean;
  message: string;
  data?: unknown;
}

const config = useRuntimeConfig();
const { data: healthData, error, pending, refresh } = useLazyFetch<HealthResponse>('/health', {
  baseURL: config.public.apiBaseUrl,
});
</script>

<template>
  <UContainer class="py-8">
    <UCard>
      <template #header>
        <h1 class="text-2xl font-bold">ヘルスチェック</h1>
      </template>

      <div class="space-y-4">
        <!-- ローディング状態 -->
        <div v-if="pending" class="flex items-center justify-center py-8">
          <UIcon name="i-heroicons-arrow-path" class="animate-spin text-4xl" />
          <span class="ml-2 text-gray-600">チェック中...</span>
        </div>

        <!-- エラー状態 -->
        <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
          <div class="flex items-start">
            <UIcon name="i-heroicons-x-circle" class="text-red-500 text-2xl mr-2 shrink-0" />
            <div>
              <h3 class="font-semibold text-red-800">エラーが発生しました</h3>
              <p class="text-red-600 mt-1">{{ error.message }}</p>
            </div>
          </div>
        </div>

        <!-- 成功状態 -->
        <div v-else-if="healthData" class="p-4 bg-green-50 border border-green-200 rounded-lg">
          <div class="flex items-start">
            <UIcon name="i-heroicons-check-circle" class="text-green-500 text-2xl mr-2 shrink-0" />
            <div>
              <h3 class="font-semibold text-green-800">システムは正常に動作しています</h3>
              <p class="text-green-600 mt-1">{{ healthData.message }}</p>

              <!-- レスポンスの詳細を表示 -->
              <details class="mt-4">
                <summary class="cursor-pointer text-sm text-green-700 hover:text-green-900">
                  レスポンスの詳細を表示
                </summary>
                <pre class="mt-2 p-3 bg-white border border-green-300 rounded text-xs overflow-x-auto">{{ JSON.stringify(healthData, null, 2) }}</pre>
              </details>
            </div>
          </div>
        </div>

        <!-- 再チェックボタン -->
        <div class="flex justify-center pt-4">
          <UButton
            color="primary"
            icon="i-heroicons-arrow-path"
            :loading="pending"
            @click="refresh()"
          >
            再チェック
          </UButton>
        </div>

        <!-- システム情報 -->
        <div class="mt-8 p-4 bg-gray-50 border border-gray-200 rounded-lg">
          <h3 class="font-semibold text-gray-800 mb-2">システム情報</h3>
          <dl class="space-y-2 text-sm">
            <div class="flex">
              <dt class="text-gray-600 w-32">エンドポイント:</dt>
              <dd class="text-gray-900 font-mono">/health</dd>
            </div>
            <div class="flex">
              <dt class="text-gray-600 w-32">チェック時刻:</dt>
              <dd class="text-gray-900">{{ new Date().toLocaleString('ja-JP') }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </UCard>
  </UContainer>
</template>
