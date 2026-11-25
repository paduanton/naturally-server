<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\Phone;
use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserPhoneChangeTest extends BrowserTestCase
{
    private function actor(): User
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return $user;
    }

    public function testPatchUpdatesOnlyRequestedFieldsAndPromotesWithinTheOwnedCollection(): void
    {
        $user = $this->actor();
        $first = Phone::factory()->forUser($user->id)->asMain()->create();
        $second = Phone::factory()->forUser($user->id)->create();
        $path = '/api/v1/users/me/phones/'.$second->id;
        $this->browser('PATCH', $path, ['label' => ' Travel ', 'main' => true])->assertOk()->assertJsonPath('data.label', 'Travel')->assertJsonPath('data.main', true)->assertHeader('Cache-Control', 'no-store, private');
        self::assertFalse($first->fresh()->main);
        $this->browser('PATCH', $path, ['label' => 'Office'])->assertOk()->assertJsonPath('data.main', true);
        $this->browser('PATCH', $path, ['main' => false])->assertUnprocessable();
        self::assertTrue($second->fresh()->main);
        self::assertSame($second->number->value, $second->fresh()->number->value);
        self::assertSame(1, Phone::where('user_id', $user->id)->where('main', true)->count());
    }

    public function testPatchRequiresSessionCsrfAndRecentAuthentication(): void
    {
        $this->bootstrapBrowser();
        $this->browser('PATCH', '/api/v1/users/me/phones/1', ['label' => 'Changed'])->assertUnauthorized();
        $user = $this->actor();
        $phone = Phone::factory()->forUser($user->id)->asMain()->create();
        $path = '/api/v1/users/me/phones/'.$phone->id;
        $this->browser('PATCH', $path, ['label' => 'Changed'], csrf: false)->assertStatus(419);
        Date::setTestNow(Date::now()->addMinutes(16));
        try { $this->browser('PATCH', $path, ['label' => 'Changed'])->assertStatus(423); }
        finally { Date::setTestNow(); }
        self::assertSame('mobile', $phone->fresh()->label);
    }

    public function testForeignMissingDeletedAndInvalidIdsNeverModifyAnotherContact(): void
    {
        $user = $this->actor();
        $foreign = Phone::factory()->forUser(User::factory()->create()->id)->asMain()->create();
        $deleted = Phone::factory()->forUser($user->id)->create(); $deleted->delete();
        foreach ([$foreign->id, $deleted->id, 999999, '0', '9223372036854775808'] as $id) {
            $this->browser('PATCH', '/api/v1/users/me/phones/'.$id, ['label' => 'Changed'])->assertNotFound();
        }
        self::assertSame('mobile', $foreign->fresh()->label);
    }

    public function testPatchRejectsUnknownFieldsNullsAndNonBooleanMainWithoutPartialWrites(): void
    {
        $user = $this->actor();
        $phone = Phone::factory()->forUser($user->id)->asMain()->create();
        foreach ([[], ['label' => null], ['main' => null], ['main' => '1'], ['label' => 'Changed', 'number' => '+5511999990001'], ['label' => 'Changed', 'user_id' => 999], ['label' => 'Changed', 'country_code' => 'BR']] as $data) {
            $this->browser('PATCH', '/api/v1/users/me/phones/'.$phone->id, $data)->assertUnprocessable();
        }
        self::assertSame('mobile', $phone->fresh()->label);
    }

    public function testPatchConsumesTheSharedMutationBudget(): void
    {
        $user = $this->actor();
        $phone = Phone::factory()->forUser($user->id)->asMain()->create();
        for ($i = 0; $i < 9; $i++) { $this->browser('DELETE', '/api/v1/users/me/phones/999999')->assertNoContent(); }
        $this->browser('PATCH', '/api/v1/users/me/phones/'.$phone->id, ['label' => 'Changed'])->assertOk();
        $this->browser('POST', '/api/v1/users/me/phones', ['label' => 'Mobile', 'country_code' => 'BR', 'number' => '+5511999990001', 'main' => false])->assertStatus(429);
        self::assertSame('Changed', $phone->fresh()->label);
    }
}
