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
          プライバシーポリシー
        </h1>

        <!-- 右側のスペーサー -->
        <div class="w-10" />
      </div>
    </header>

    <main class="container mx-auto max-w-3xl px-4 py-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
          プライバシーポリシー
        </h2>

        <div class="prose dark:prose-invert max-w-none">
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            「最後はいつ？」（以下「本サービス」）は、ユーザーの個人情報について以下のとおりプライバシーポリシー（以下「本ポリシー」）を定めます。
          </p>

          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-2">
            第1条（収集する情報）
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            本サービスは、以下の情報を収集することがあります。
          </p>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-400 mb-4">
            <li>メールアドレス</li>
            <li>ニックネーム</li>
            <li>ユーザーが作成したイベントおよび履歴データ</li>
            <li>アプリの設定情報</li>
          </ul>

          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-2">
            第2条（情報の利用目的）
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            収集した情報は、以下の目的で利用します。
          </p>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-400 mb-4">
            <li>本サービスの提供・運営のため</li>
            <li>ユーザーからのお問い合わせに回答するため</li>
            <li>メンテナンス、重要なお知らせなど必要に応じたご連絡のため</li>
            <li>利用規約に違反したユーザーの特定および対応のため</li>
            <li>サービスの改善および新機能の開発のため</li>
          </ul>

          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-2">
            第3条（情報の第三者提供）
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            本サービスは、以下の場合を除き、ユーザーの同意なく個人情報を第三者に提供することはありません。
          </p>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-400 mb-4">
            <li>法令に基づく場合</li>
            <li>人の生命、身体または財産の保護のために必要がある場合</li>
            <li>公衆衛生の向上または児童の健全な育成の推進のために特に必要がある場合</li>
          </ul>

          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-2">
            第4条（セキュリティ）
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            本サービスは、個人情報の漏洩、滅失またはき損の防止その他の個人情報の安全管理のために必要かつ適切な措置を講じます。
          </p>

          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-2">
            第5条（プライバシーポリシーの変更）
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            本ポリシーの内容は、法令その他本ポリシーに別段の定めのある事項を除いて、ユーザーに通知することなく、変更することができるものとします。
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
