<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\PhoneNumberService;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        if (! empty($input['mobile_number'])) {
            try {
                $input['mobile_number'] = app(PhoneNumberService::class)->normalize($input['mobile_number']);
            } catch (InvalidArgumentException) {
                // Let the profile validation rules return the normal validation response.
            }
        }

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $data = [
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ];

        if (! empty($input['mobile_number'])) {
            $data['mobile_number'] = $input['mobile_number'];
        }

        return User::create($data);
    }
}
