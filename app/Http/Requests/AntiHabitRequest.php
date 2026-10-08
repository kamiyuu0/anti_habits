<?php

namespace App\Http\Requests;

use App\Models\AntiHabit;
use App\Models\Tag;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class AntiHabitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_public' => $this->boolean('is_public'),
            'goal_days' => $this->filled('goal_days') ? $this->input('goal_days') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:'.AntiHabit::MAX_TITLE_LENGTH],
            'description' => ['nullable', 'string', 'max:'.AntiHabit::MAX_DESCRIPTION_LENGTH],
            'tag_names' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) {
                foreach (AntiHabit::parseTagNames($value) as $name) {
                    if (mb_strlen($name) > Tag::MAX_NAME_LENGTH) {
                        $fail("タグ「{$name}」は".Tag::MAX_NAME_LENGTH.'文字以内で入力してください');
                    }
                }
            }],
            'is_public' => ['boolean'],
            'goal_days' => ['nullable', 'integer', 'between:1,365'],
        ];
    }
}
