<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ISO 8601形式の日付バリデーションルール
 *
 * 以下の形式を受け入れます：
 * - Y-m-d\TH:i:s\Z (例: 2024-01-28T10:30:00Z)
 * - Y-m-d\TH:i:sP (例: 2024-01-28T10:30:00+09:00)
 */
class Iso8601DateFormat implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('実行日時はISO 8601形式で入力してください');

            return;
        }

        $formats = [
            'Y-m-d\TH:i:s\Z',
            'Y-m-d\TH:i:sP',
        ];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return;
            }
        }

        $fail('実行日時はISO 8601形式で入力してください');
    }
}
