<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Mail\Auth\RegistrationAttemptNotification;
use App\Mail\Auth\VerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 認証コード送信APIのテスト
 */
class SendCodeTest extends TestCase
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
     * 新規ユーザーの認証コード送信成功のテスト
     */
    #[Test]
    public function test_send_code_success_for_new_user(): void
    {
        $requestBody = [
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'nickname' => 'NewUser',
        ];

        $response = $this->postJson('/auth/register/send-code', $requestBody);

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', true)
            ->has('data', fn (AssertableJson $data) => $data
                ->where('message', '認証コードを送信しました')
                ->where('email', 'newuser@example.com')
                ->has('expiresIn')
            )
            ->has('meta')
        );

        // 認証コードメールが送信されたことを確認
        Mail::assertSent(VerificationCodeMail::class, function ($mail) {
            return $mail->hasTo('newuser@example.com');
        });

        // Redisにデータが保存されたことを確認
        $this->assertNotNull(Redis::get('registration:newuser@example.com'));
    }

    /**
     * 既存ユーザーの認証コード送信（通知メール送信）のテスト
     */
    #[Test]
    public function test_send_code_for_existing_user(): void
    {
        // 既存ユーザーを作成
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $requestBody = [
            'email' => 'existing@example.com',
            'password' => 'password123',
            'nickname' => 'Existing',
        ];

        $response = $this->postJson('/auth/register/send-code', $requestBody);

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', true)
            ->has('data', fn (AssertableJson $data) => $data
                ->where('message', '認証コードを送信しました')
                ->where('email', 'existing@example.com')
                ->has('expiresIn')
            )
            ->etc()
        );

        // 既存ユーザー通知メールが送信されたことを確認
        Mail::assertSent(RegistrationAttemptNotification::class, function ($mail) {
            return $mail->hasTo('existing@example.com');
        });

        // Redisにデータが保存されたことを確認
        $data = Redis::get('registration:existing@example.com');
        $this->assertNotNull($data);
        $registrationData = json_decode($data, true);
        $this->assertTrue($registrationData['isExistingUser']);
    }

    /**
     * レート制限超過のテスト
     */
    #[Test]
    public function test_send_code_rate_limit_exceeded(): void
    {
        $email = 'ratelimit@example.com';
        // レート制限カウンターを上限に設定
        Redis::setex("rate_limit:registration:{$email}", 3600, 5);

        $requestBody = [
            'email' => $email,
            'password' => 'password123',
            'nickname' => 'RateLimit',
        ];

        $response = $this->postJson('/auth/register/send-code', $requestBody);

        $response->assertStatus(429);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', false)
            ->has('error', fn (AssertableJson $error) => $error
                ->where('code', 'RATE_LIMIT_EXCEEDED')
                ->where('message', '送信回数の上限に達しました。しばらくしてからお試しください')
            )
        );
    }

    /**
     * バリデーションエラーのテスト
     */
    #[Test]
    #[DataProvider('dataProviderInvalidParameters')]
    public function test_send_code_validation_error(array $requestBody, string $expectedMessage): void
    {
        $response = $this->postJson('/auth/register/send-code', $requestBody);

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
                    'password' => 'password123',
                    'nickname' => 'User',
                ],
                'expectedMessage' => 'メールアドレスを入力してください',
            ],
            'emailフォーマット誤り' => [
                'requestBody' => [
                    'email' => 'invalid-email',
                    'password' => 'password123',
                    'nickname' => 'User',
                ],
                'expectedMessage' => '有効なメールアドレスを入力してください',
            ],
            'email文字数超過' => [
                'requestBody' => [
                    'email' => str_repeat('a', 244) . '@example.com',
                    'password' => 'password123',
                    'nickname' => 'User',
                ],
                'expectedMessage' => 'メールアドレスは255文字以内で入力してください',
            ],
            'password空文字' => [
                'requestBody' => [
                    'email' => 'user@example.com',
                    'password' => '',
                    'nickname' => 'User',
                ],
                'expectedMessage' => 'パスワードを入力してください',
            ],
            'password文字数不足' => [
                'requestBody' => [
                    'email' => 'user@example.com',
                    'password' => 'short1',
                    'nickname' => 'User',
                ],
                'expectedMessage' => 'パスワードは、8文字から32文字にしてください。',
            ],
            'password文字数超過' => [
                'requestBody' => [
                    'email' => 'user@example.com',
                    'password' => str_repeat('a', 32) . '1',
                    'nickname' => 'User',
                ],
                'expectedMessage' => 'パスワードは、8文字から32文字にしてください。',
            ],
            'nickname空文字' => [
                'requestBody' => [
                    'email' => 'user@example.com',
                    'password' => 'password123',
                    'nickname' => '',
                ],
                'expectedMessage' => 'ニックネームを入力してください',
            ],
            'nickname文字数超過' => [
                'requestBody' => [
                    'email' => 'user@example.com',
                    'password' => 'password123',
                    'nickname' => str_repeat('あ', 11),
                ],
                'expectedMessage' => 'ニックネームは10文字以内で入力してください',
            ],
        ];
    }
}
