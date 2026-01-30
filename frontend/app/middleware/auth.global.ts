/**
 * 認証ミドルウェア
 * ログイン状態でのみアクセスを許可する
 */
export default defineNuxtRouteMiddleware((to) => {
  // アクセストークンの確認
  const token = useCookie('access_token');

  // 認証不要なページ（ログイン、会員登録など）
  const publicPages = ['/login', '/member-register', '/health'];
  const isPublicPage = publicPages.some(page => to.path.startsWith(page));

  // 公開ページの場合はスキップ
  if (isPublicPage) {
    return;
  }

  // 未認証の場合はログイン画面へリダイレクト
  if (!token.value) {
    return navigateTo('/login');
  }
});
