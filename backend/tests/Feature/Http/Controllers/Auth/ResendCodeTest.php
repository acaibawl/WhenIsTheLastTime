<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Mail\Auth\RegistrationAttemptNotification;
use App\Mail\Auth\VerificationCodeMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 認証コード再送信APIのテスト
 */
class ResendCodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Redisのクリーンアップ
        Redis::flushdb();
        // メールのモック
        Mail::fake();
    }

    /**
     * 認証コード再送信成功のテスト
     */
    #[Test]
    public function test_resend_code_success(): void
    {
        $email = 'user@example.com';
        $oldCode = '123456';
        $password = 'password123';
        $nickname = 'User';

        // Redisに登録データを保存（クールダウン時間を過ぎている）
        $registrationData = [
            'email' => $email,
            'passwordHash' => Hash::make($password),
            'nickname' => $nickname,
            'code' => Hash::make($oldCode),
            'attempts' => 2,
            'lastSentAt' => time() - 61, // 61秒前
            'createdAt' => time() - 100,
            'isExistingUser' => false,
        ];
        Redis::setex("registration:{$email}", 600, json_encode($registrationData));

        $requestBody = [
            'email' => $email,
        ];

        $response = $this->postJson('/auth/register/resend-code', $requestBody);

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', true)
            ->has('data', fn (AssertableJson $data) => $data
                ->where('message', '認証コードを再送信しました')
                ->where('email', $email)
                ->has('expiresIn')
            )
        );

        // 認証コードメールが送信されたことを確認
        Mail::assertSent(VerificationCodeMail::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });

        // Redisのデータが更新されたことを確認
        $data = Redis::get("registration:{$email}");
        $this->assertNotNull($data);
        $updatedData = json_decode($data, true);
        // 試行回数がリセットされたことを確認
        $this->assertEquals(0, $updatedData['attempts']);
        // lastSentAtが更新されたことを確認
        $this->assertGreaterThan($registrationData['lastSentAt'], $updatedData['lastSentAt']);
    }

    /**
     * 既存ユーザーの再送信（通知メール送信）のテスト
     */
    #[Test]
    public function test_resend_code_for_existing_user(): void
    {
        $email = 'existing@example.com';
        $oldCode = '123456';

        // Redisに登録データを保存（既存ユーザー）
        $registrationData = [
            'email' => $email,
            'passwordHash' => '',
            'nickname' => '',
            'code' => Hash::make($oldCode),
            'attempts' => 1,
            'lastSentAt' => time() - 61,
            'createdAt' => time() - 100,
            'isExistingUser' => true,
        ];
        Redis::setex("registration:{$email}", 600, json_encode($registrationData));

        $requestBody = [
            'email' => $email,
        ];

        $response = $this->postJson('/auth/register/resend-code', $requestBody);

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', true)
            ->has('data')
        );

        // 既存ユーザー通知メールが送信されたことを確認
        Mail::assertSent(RegistrationAttemptNotification::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    /**
     * 登録データが存在しない場合のテスト
     */
    #[Test]
    public function test_resend_code_not_found(): void
    {
        $requestBody = [
            'email' => 'notfound@example.com',
        ];

        $response = $this->postJson('/auth/register/resend-code', $requestBody);

        $response->assertStatus(400);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', false)
            ->has('error', fn (AssertableJson $error) => $error
                ->where('code', 'INVALID_VERIFICATION_CODE')
                ->where('message', '認証コードが正しくありません')
            )
        );
    }

    /**
     * クールダウン期間中の再送信エラーのテスト
     */
    #[Test]
    public function test_resend_code_cooldown(): void
    {
        $email = 'user@example.com';
        $code = '123456';

        // Redisに登録データを保存（クールダウン期間内）
        $registrationData = [
            'email' => $email,
            'passwordHash' => Hash::make('password123'),
            'nickname' => 'User',
            'code' => Hash::make($code),
            'attempts' => 0,
            'lastSentAt' => time() - 30, // 30秒前（クールダウン60秒以内）
            'createdAt' => time() - 100,
            'isExistingUser' => false,
        ];
        Redis::setex("registration:{$email}", 600, json_encode($registrationData));

        $requestBody = [
            'email' => $email,
        ];

        $response = $this->postJson('/auth/register/resend-code', $requestBody);

        $response->assertStatus(429);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', false)
            ->has('error', fn (AssertableJson $error) => $error
                ->where('code', 'RESEND_COOLDOWN')
                ->where('message', '再送信は60秒後に可能です')
                ->has('details', fn (AssertableJson $details) => $details
                    ->has('retryAfter')
                )
            )
        );
    }

    /**
     * バリデーションエラーのテスト
     */
    #[Test]
    #[DataProvider('dataProviderInvalidParameters')]
    public function test_resend_code_validation_error(array $requestBody, string $expectedMessage): void
    {
        $response = $this->postJson('/auth/register/resend-code', $requestBody);

        $response->assertUnprocessable();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('message', $expectedMessage)
            ->etc()
        );
    }

    /**
     * @return array[]
     */
    public static function dataProviderInvalidParameters(): array
    {
        return [
            'email空文字' => [
                'requestBody' => [
                    'email' => '',
                ],
                'expectedMessage' => 'メールアドレスを入力してください',
            ],
            'emailフォーマット誤り' => [
                'requestBody' => [
                    'email' => 'invalid-email',
                ],
                'expectedMessage' => '有効なメールアドレスを入力してください',
            ],
            'email文字数超過' => [
                'requestBody' => [
                    'email' => str_repeat('a', 244) . '@example.com',
                ],
                'expectedMessage' => 'メールアドレスの文字数は、255文字以下である必要があります。',
            ],
        ];
    }
}
