<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>アカウント登録のお知らせ</title>
</head>
<body style="font-family: sans-serif; padding: 20px;">
  <div style="max-width: 600px; margin: 0 auto;">
    <h1 style="color: #3B82F6;">最後はいつ？</h1>
    
    <p>あなたのメールアドレスを使用して、新規アカウントの登録が試みられました。</p>
    
    <div style="background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 16px; margin: 20px 0;">
      <p style="margin: 0; color: #92400E;">
        <strong>既にアカウントをお持ちの場合：</strong><br>
        この操作に心当たりがない場合は、このメールを無視してください。<br>
        あなたのアカウントは安全です。
      </p>
    </div>
    
    <p><strong>パスワードをお忘れの場合：</strong></p>
    <p>
      <a href="{{ config('app.url') }}/reset-password" 
         style="display: inline-block; background: #3B82F6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
        パスワードをリセット
      </a>
    </p>
    
    <p style="color: #6B7280; font-size: 14px;">
      ご不明な点がございましたら、サポートまでお問い合わせください。
    </p>
    
    <hr style="border: none; border-top: 1px solid #E5E7EB; margin: 20px 0;">
    
    <p style="color: #9CA3AF; font-size: 12px;">
      最後はいつ？ サポートチーム<br>
      <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
    </p>
  </div>
</body>
</html>
