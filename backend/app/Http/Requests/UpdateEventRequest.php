<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'categoryIcon' => ['required', 'string', 'in:' . implode(',', Event::CATEGORY_ICONS)],
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
            'name.max' => 'イベント名は100文字以内で入力してください',
            'categoryIcon.required' => 'カテゴリーアイコンは必須です',
            'categoryIcon.in' => '無効なカテゴリーアイコンです',
        ];
    }
}
