<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\ChangePassword;
use App\Modules\Identity\Application\CredentialChangeDenied;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\PasswordChangeInput;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

final readonly class UpdatePassword implements UpdatesUserPasswords
{
    public function __construct(private ChangePassword $change, private Request $request) {}

    public function update($user, #[\SensitiveParameter] array $input): void
    {
        $data = Validator::make($input, ['password' => 'required|string|min:15|max:1024|confirmed'])->validate();
        try {
            $this->change->handle(new PasswordChangeInput((int) $user->getAuthIdentifier(), $this->request->session()->getId(), $data['password']));
        } catch (InactiveSession) {
            auth()->guard('web')->logout();
            $this->request->session()->invalidate();
            $this->request->session()->regenerateToken();
            throw new AuthenticationException();
        } catch (CredentialChangeDenied|\InvalidArgumentException) {
            throw ValidationException::withMessages(['password' => ['Credential change is unavailable.']]);
        }
    }
}
