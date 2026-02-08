<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str as SupportStr;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ソーシャル認証（Twitter/X）のテスト
 */
class SocialAuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('app.frontend_url', 'http://localhost:3000');
    }

    /**
     * Socialiteユーザーのモックを作成するヘルパー
     */
    private function mockSocialiteUser(string $id, ?string $nickname = 'test_user', ?string $name = 'Test User', ?string $email = 'test@example.com'): void
    {
        $socialUser = Mockery::mock(\Laravel\Socialite\AbstractUser::class);
        $socialUser->shouldReceive('getId')->andReturn($id);
        $socialUser->shouldReceive('getNickname')->andReturn($nickname);
        $socialUser->shouldReceive('getName')->andReturn($name);
        $socialUser->shouldReceive('getEmail')->andReturn($email);

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('twitter')->andReturn($driver);
    }

    /**
     * Twitter認証リダイレクトが正常に動作すること
     */
    #[Test]
    public function test_redirect_to_twitter(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('redirect')
            ->andReturn(redirect('https://api.twitter.com/oauth/authorize'));

        Socialite::shouldReceive('driver')->with('twitter')->andReturn($provider);

        $response = $this->get('/auth/social/twitter/redirect');

        // Socialiteのリダイレクトが発生するか確認
        $response->assertRedirect();
        $this->assertStringContainsString('twitter.com', $response->headers->get('Location'));
    }

    /**
     * 未対応プロバイダーでリダイレクトするとエラーが返ること
     */
    #[Test]
    public function test_redirect_unsupported_provider(): void
    {
        $response = $this->getJson('/auth/social/unsupported/redirect');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'UNSUPPORTED_PROVIDER',
            ],
        ]);
    }

    /**
     * 未対応プロバイダーでコールバックするとエラーが返ること
     */
    #[Test]
    public function test_callback_unsupported_provider(): void
    {
        $response = $this->getJson('/auth/social/unsupported/callback');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'UNSUPPORTED_PROVIDER',
            ],
        ]);
    }

    /**
     * 新規ユーザーのTwitterコールバックでユーザーが作成されること
     */
    #[Test]
    public function test_callback_creates_new_user(): void
    {
        $this->mockSocialiteUser('twitter_12345', 'test_user', 'Test User', 'twitter@example.com');

        $response = $this->get('/auth/social/twitter/callback');

        // フロントエンドへのリダイレクトを確認
        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('token=', $redirectUrl);
        $this->assertStringContainsString('provider=twitter', $redirectUrl);

        // ユーザーが作成されたことを確認
        $this->assertDatabaseHas('users', [
            'twitter_id' => 'twitter_12345',
            'email' => 'twitter@example.com',
            'nickname' => 'test_user',
        ]);

        // デフォルト設定が作成されたことを確認
        $user = User::where('twitter_id', 'twitter_12345')->first();
        $this->assertNotNull($user);
        $this->assertNotNull(UserSetting::where('user_id', $user->id)->first());
    }

    /**
     * 既存のTwitterユーザーがコールバックでログインできること
     */
    #[Test]
    public function test_callback_logs_in_existing_twitter_user(): void
    {
        // 既存ユーザーを作成
        $existingUser = User::factory()->create([
            'twitter_id' => 'twitter_67890',
            'email' => 'existing@example.com',
            'password_hash' => SupportStr::random(32),
        ]);

        $this->mockSocialiteUser('twitter_67890', 'existing_user', 'Existing User', 'existing@example.com');

        $response = $this->get('/auth/social/twitter/callback');

        // フロントエンドへのリダイレクトを確認
        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('token=', $redirectUrl);

        // 新しいユーザーが作成されていないことを確認
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * メールが一致する既存ユーザーにTwitter IDが紐付けられること
     */
    #[Test]
    public function test_callback_links_twitter_to_existing_email_user(): void
    {
        // メール認証で作成された既存ユーザー
        $existingUser = User::factory()->create([
            'email' => 'user@example.com',
            'twitter_id' => null,
            'password_hash' => \Hash::make('password'),
        ]);

        $this->mockSocialiteUser('twitter_99999', 'twitter_nick', 'Twitter Name', 'user@example.com');

        $response = $this->get('/auth/social/twitter/callback');

        // リダイレクトを確認
        $response->assertRedirect();

        // 既存ユーザーにTwitter IDが紐付けられたことを確認
        $existingUser->refresh();
        $this->assertEquals('twitter_99999', $existingUser->twitter_id);

        // 新しいユーザーが作成されていないことを確認
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * メールアドレスが取得できない場合、仮のメールが設定されること
     */
    #[Test]
    public function test_callback_with_no_email_creates_placeholder(): void
    {
        $this->mockSocialiteUser('twitter_no_email', 'no_email_user', 'No Email User', null);

        $response = $this->get('/auth/social/twitter/callback');

        $response->assertRedirect();

        // 仮のメールアドレスが設定されていることを確認
        $this->assertDatabaseHas('users', [
            'twitter_id' => 'twitter_no_email',
            'email' => 'twitter_twitter_no_email@social.witlt.local',
        ]);
    }

    /**
     * 認証プロバイダーでエラーが発生した場合、ログインページにリダイレクトすること
     */
    #[Test]
    public function test_callback_handles_provider_error(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andThrow(new \Exception('Provider error'));

        Socialite::shouldReceive('driver')->with('twitter')->andReturn($provider);

        $response = $this->get('/auth/social/twitter/callback');

        // エラー時はログインページにリダイレクト
        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('error=social_auth_failed', $redirectUrl);
    }
}
