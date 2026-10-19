<?php

namespace App\Concerns;

use App\Models\User;
use App\Services\PhoneNumberService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|\Stringable|\Closure|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            'mobile_number' => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    try {
                        app(PhoneNumberService::class)->normalize((string) $value);
                    } catch (\InvalidArgumentException $exception) {
                        $fail($exception->getMessage());
                    }
                },
                $userId === null
                    ? Rule::unique(User::class, 'mobile_number')
                    : Rule::unique(User::class, 'mobile_number')->ignore($userId),
            ],
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
