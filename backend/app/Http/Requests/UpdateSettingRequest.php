<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
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
            // export
            'export' => ['sometimes', 'array'],
            'export.lastExportedAt' => ['nullable', 'date', 'iso8601'],

            // notification
            'notification' => ['sometimes', 'array'],
            'notification.reminder' => ['sometimes', 'array'],
            'notification.reminder.enabled' => ['sometimes', 'boolean'],
            'notification.reminder.timing' => ['sometimes', 'array'],
            'notification.reminder.timing.type' => ['sometimes', 'string', 'in:daily,weekly,monthly'],
            'notification.reminder.timing.time' => ['sometimes', 'string', 'regex:/^([01]\d|2[0-3]):([0-5]\d)$/'],
            'notification.reminder.timing.dayOfWeek' => ['nullable', 'integer', 'min:0', 'max:6'],
            'notification.reminder.timing.dayOfMonth' => ['nullable', 'integer', 'min:1', 'max:31'],
            'notification.reminder.targetEvents' => ['sometimes', 'string', 'in:all,week,month,year'],

            // misc
            'misc' => ['sometimes', 'array'],
            'misc.showTutorial' => ['sometimes', 'boolean'],
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
            'export.array' => 'exportは配列形式で指定してください',
            'export.lastExportedAt.date' => 'lastExportedAtは日付形式で指定してください',
            'export.lastExportedAt.iso8601' => 'lastExportedAtはISO8601形式で指定してください',

            'notification.array' => 'notificationは配列形式で指定してください',
            'notification.reminder.array' => 'reminderは配列形式で指定してください',
            'notification.reminder.enabled.boolean' => 'enabledはboolean形式で指定してください',
            'notification.reminder.timing.array' => 'timingは配列形式で指定してください',
            'notification.reminder.timing.type.in' => 'typeはdaily、weekly、monthlyのいずれかを指定してください',
            'notification.reminder.timing.time.regex' => 'timeはHH:MM形式で指定してください',
            'notification.reminder.timing.dayOfWeek.integer' => 'dayOfWeekは整数で指定してください',
            'notification.reminder.timing.dayOfWeek.min' => 'dayOfWeekは0以上で指定してください',
            'notification.reminder.timing.dayOfWeek.max' => 'dayOfWeekは6以下で指定してください',
            'notification.reminder.timing.dayOfMonth.integer' => 'dayOfMonthは整数で指定してください',
            'notification.reminder.timing.dayOfMonth.min' => 'dayOfMonthは1以上で指定してください',
            'notification.reminder.timing.dayOfMonth.max' => 'dayOfMonthは31以下で指定してください',
            'notification.reminder.targetEvents.in' => 'targetEventsはall、week、month、yearのいずれかを指定してください',

            'misc.array' => 'miscは配列形式で指定してください',
            'misc.showTutorial.boolean' => 'showTutorialはboolean形式で指定してください',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // キャメルケースからスネークケースへの変換は不要（JSONで受け取るため）
    }
}
