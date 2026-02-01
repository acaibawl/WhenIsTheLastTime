<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- ヘッダー -->
    <header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="container mx-auto px-4 py-3 flex items-center justify-between">
        <!-- 戻るボタン -->
        <button
          class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          aria-label="戻る"
          @click="navigateBack"
        >
          <UIcon name="i-lucide-arrow-left" class="w-6 h-6" />
        </button>

        <!-- タイトル -->
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white flex-1 text-center">
          利用規約
        </h1>

        <!-- 右側のスペーサー -->
        <div class="w-10" />
      </div>
    </header>

    <main class="container mx-auto max-w-3xl px-4 py-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
          利用規約
        </h2>

        <div class="prose dark:prose-invert max-w-none">
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            この利用規約（以下「本規約」）は、「最後はいつ？」（以下「本サービス」）の利用条件を定めるものです。
          </p>

          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-2">
            第1条（適用）
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            本規約は、ユーザーと本サービス運営者との間の本サービスの利用に関わる一切の関係に適用されるものとします。
          </p>

          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-2">
            第2条（禁止事項）
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            ユーザーは、本サービスの利用にあたり、以下の行為をしてはなりません。
          </p>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-400 mb-4">
            <li>法令または公序良俗に違反する行為</li>
            <li>犯罪行為に関連する行為</li>
            <li>本サービスの運営を妨害するおそれのある行為</li>
            <li>他のユーザーに関する個人情報等を収集または蓄積する行為</li>
            <li>不正アクセスをし、またはこれを試みる行為</li>
          </ul>

          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-2">
            第3条（免責事項）
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            本サービス運営者は、本サービスに事実上または法律上の瑕疵がないことを明示的にも黙示的にも保証しておりません。
          </p>

          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-2">
            第4条（サービス内容の変更等）
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            本サービス運営者は、ユーザーに通知することなく、本サービスの内容を変更しまたは本サービスの提供を中止することができるものとし、これによってユーザーに生じた損害について一切の責任を負いません。
          </p>

          <p class="text-gray-500 dark:text-gray-400 mt-8 text-sm">
            制定日: 2026年1月1日
          </p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
const router = useRouter();

const navigateBack = () => {
  router.back();
};
</script>
