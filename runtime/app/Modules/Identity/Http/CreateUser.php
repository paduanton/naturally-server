<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\RegisterAccount;
use App\Modules\Identity\Application\RegistrationInput;
use App\Modules\Identity\Application\RegistrationConflict;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final readonly class CreateUser implements CreatesNewUsers
{
    public function __construct(private RegisterAccount $register, private UserProvider $users) {}

    public function create(#[\SensitiveParameter] array $input)
    {
        $rules = ['name' => 'required|string|max:255', 'username' => ['required', 'string', 'min:4', 'max:20', 'regex:/\A[a-zA-Z0-9]+(?:[._][a-zA-Z0-9]+)?\z/D'], 'email' => 'required|string|email:rfc|max:255', 'password' => 'required|string|min:15|max:1024|confirmed'];
        foreach (['id', 'user_id', 'email_verified_at', 'deleted_at', 'password_hash', 'remember', 'remember_me'] as $field) { $rules[$field] = 'prohibited'; }
        $data = Validator::make($input, $rules)->validate();
        try {
            $account = $this->register->handle(new RegistrationInput($data['name'], $data['username'], $data['email'], $data['password']));
        } catch (RegistrationConflict|\InvalidArgumentException) {
            throw ValidationException::withMessages(['account' => ['Account details are unavailable.']]);
        }
        return $this->users->retrieveById($account->id) ?? throw new \LogicException('Registered account unavailable.');
    }
}
