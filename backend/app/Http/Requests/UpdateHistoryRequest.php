<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\Iso8601DateFormat;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHistoryRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'executedAt' => ['required', 'date', new Iso8601DateFormat(), 'before_or_equal:now'],
            'memo' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'executedAt.required' => '実行日時は必須です',
            'executedAt.date' => '実行日時の形式が正しくありません',
            'executedAt.before_or_equal' => '未来の日時は指定できません',
            'memo.max' => 'メモは500文字以内で入力してください',
        ];
    }
}
