<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use App\Rules\Iso8601DateFormat;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'categoryIcon' => ['required', 'string', 'in:' . implode(',', Event::CATEGORY_ICONS)],
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
            'name.required' => 'イベント名は必須です',
            'name.min' => 'イベント名は1文字以上で入力してください',
            'name.max' => 'イベント名は100文字以内で入力してください',
            'categoryIcon.required' => 'カテゴリーアイコンは必須です',
            'categoryIcon.in' => '無効なカテゴリーアイコンです',
            'executedAt.required' => '実行日時は必須です',
            'executedAt.date' => '実行日時の形式が正しくありません',
            'executedAt.before_or_equal' => '未来の日時は指定できません',
            'memo.max' => 'メモは500文字以内で入力してください',
        ];
    }
}
