<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>認証コード</title>
</head>
<body style="font-family: sans-serif; padding: 20px;">
  <div style="max-width: 600px; margin: 0 auto;">
    <h1 style="color: #3B82F6;">最後はいつ？</h1>
    <p>会員登録を完了するには、以下の認証コードを入力してください。</p>
    
    <div style="background: #F3F4F6; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;">
      <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px;">{{ $code }}</span>
    </div>
    
    <p style="color: #6B7280; font-size: 14px;">
      ※ このコードは10分間有効です。<br>
      ※ このメールに心当たりがない場合は、無視してください。
    </p>
    
    <hr style="border: none; border-top: 1px solid #E5E7EB; margin: 20px 0;">
    
    <p style="color: #9CA3AF; font-size: 12px;">
      最後はいつ？ サポートチーム<br>
      <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
    </p>
  </div>
</body>
</html>
