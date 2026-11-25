<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\ManagePhoneContacts;
use App\Modules\Identity\Application\PhoneContactSession;
use App\Modules\Identity\Infrastructure\Persistence\Phone;
use App\Modules\Identity\Infrastructure\Persistence\User;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class PhoneContactValidationTest extends BrowserTestCase
{
    private function actor(): array
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return [$user, new PhoneContactSession($user->id, $this->app->make('session')->driver()->getId())];
    }

    public function testApplicationRejectsWrongCountryFixedLinesAndImpossibleNumbersBeforeWriting(): void
    {
        [$user, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        foreach ([['GB', '+5511999990001'], ['BR', '+551132345678'], ['BR', '+5500999990001'], ['ZZ', '+5511999990001'], ['BR', '+5511'], ['BR', '+999123456789'], ['BR', '+12025550123']] as [$country, $number]) {
            try {
                $contacts->add($session, 'Mobile', $country, $number, true);
                self::fail('Invalid regional/mobile contact accepted.');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame('Invalid mobile phone contact.', $exception->getMessage());
                self::assertNull($exception->getPrevious());
            }
            self::assertSame(0, Phone::where('user_id', $user->id)->count());
        }
    }

    public function testValidInternationalMobileContactsPreserveCanonicalNumberAndNormalizedCountry(): void
    {
        [$user, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $first = $contacts->add($session, 'Mobile', ' br ', '+5511999990001', true);
        $second = $contacts->add($session, 'Travel', 'gb', '+447400123456', false);
        self::assertSame('BR', $first->countryCode);
        self::assertSame('+5511999990001', $first->number);
        self::assertSame('GB', $second->countryCode);
        self::assertSame('+447400123456', $second->number);
        self::assertSame(2, Phone::where('user_id', $user->id)->count());
    }
}
