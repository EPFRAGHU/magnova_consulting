<?php

namespace App\Http\Requests\Auth;

use App\Services\PhoneNumberService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RequestLoginOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile_number' => ['required', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('mobile_number');

        if (! is_string($value)) {
            return;
        }

        try {
            $this->merge(['mobile_number' => app(PhoneNumberService::class)->normalize($value)]);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['mobile_number' => $exception->getMessage()]);
        }
    }
}
