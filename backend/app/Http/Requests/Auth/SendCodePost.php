<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendCodePost extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => [
                'required',
                'string',
                'between:8,32',
                'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
            ],
            'nickname' => ['required', 'string', 'min:1', 'max:10'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => '有効なメールアドレスを入力してください',
            'email.max' => 'メールアドレスは255文字以内で入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.between' => 'パスワードは、8文字から32文字にしてください。',
            'password.regex' => 'パスワードは英字と数字を含めてください',
            'nickname.required' => 'ニックネームを入力してください',
            'nickname.min' => 'ニックネームは1文字以上で入力してください',
            'nickname.max' => 'ニックネームは10文字以内で入力してください',
        ];
    }
}
