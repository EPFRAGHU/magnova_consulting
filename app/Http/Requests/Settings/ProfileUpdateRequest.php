<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Services\PhoneNumberService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    protected function prepareForValidation(): void
    {
        $mobileNumber = $this->input('mobile_number');

        if (is_string($mobileNumber) && trim($mobileNumber) !== '') {
            try {
                $mobileNumber = app(PhoneNumberService::class)->normalize($mobileNumber);
            } catch (\InvalidArgumentException) {
                // Leave the original value in place so the validation rule reports the error.
            }
        }

        $this->merge(['mobile_number' => $mobileNumber]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }
}
