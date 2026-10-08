<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\Support\DatabaseTestCase;

// Schema behavior uses real MySQL; migration coverage is also measured by IdentitySchemaTest.
#[CoversNothing]
final class IdentityCredentialsSchemaTest extends DatabaseTestCase
{
    public function testProviderIdentityIsUniqueAcrossUsersAndPreservesOpaqueIdentifiers(): void
    {
        $connection = $this->database->connection();
        $first = $this->user('first');
        $second = $this->user('second');
        $row = ['user_id' => $first, 'provider' => 'google', 'provider_user_id' => 'Opaque-00042'];
        $connection->table('social_accounts')->insert($row);
        $row['user_id'] = $second;
        $this->assertRejected(fn () => $connection->table('social_accounts')->insert($row), 1062);
        $row['provider_user_id'] = 'opaque-00042';
        $connection->table('social_accounts')->insert($row);
        $row['provider_user_id'] = 'Opaque-00042';
        $row['provider'] = 'facebook';
        $connection->table('social_accounts')->insert($row);
        self::assertSame(3, $connection->table('social_accounts')->count());
        self::assertSame('Opaque-00042', $connection->table('social_accounts')->where('user_id', $first)->value('provider_user_id'));
    }

    public function testSocialAccountsRequireAValidUserAndKnownProvider(): void
    {
        $connection = $this->database->connection();
        $row = ['user_id' => 999, 'provider' => 'x', 'provider_user_id' => 'account'];
        $this->assertRejected(fn () => $connection->table('social_accounts')->insert($row), 1452);
        $row['user_id'] = $this->user('chef');
        $row['provider'] = 'unsupported';
        $this->assertRejected(fn () => $connection->table('social_accounts')->insert($row), 1265);
    }

    public function testDeletingOneUserPreservesOtherSocialAccounts(): void
    {
        $connection = $this->database->connection();
        $first = $this->user('first');
        $second = $this->user('second');
        $connection->table('social_accounts')->insert([
            ['user_id' => $first, 'provider' => 'google', 'provider_user_id' => 'first'],
            ['user_id' => $second, 'provider' => 'google', 'provider_user_id' => 'second'],
        ]);
        $connection->table('users')->where('id', $first)->delete();
        self::assertSame(1, $connection->table('social_accounts')->count());
        self::assertSame($second, $connection->table('social_accounts')->value('user_id'));
        $columns = $connection->getSchemaBuilder()->getColumnListing('social_accounts');
        self::assertSame([], array_intersect(['access_token', 'refresh_token', 'email'], $columns));
    }

    public function testOnlyOneRecoveryRecordCanExistPerEmail(): void
    {
        $connection = $this->database->connection();
        $row = ['email' => 'chef@example.test', 'token' => password_hash(bin2hex(random_bytes(24)), PASSWORD_ARGON2ID), 'created_at' => now()];
        $connection->table('password_reset_tokens')->insert($row);
        $row['email'] = 'CHEF@EXAMPLE.TEST';
        $this->assertRejected(fn () => $connection->table('password_reset_tokens')->insert($row), 1062);
        self::assertSame(1, $connection->table('password_reset_tokens')->count());
    }

    private function user(string $name): int
    {
        return $this->database->connection()->table('users')->insertGetId([
            'name' => $name, 'username' => $name, 'email' => $name.'@example.test',
        ]);
    }

    private function assertRejected(callable $operation, int $expectedCode): void
    {
        $code = null;
        try {
            $operation();
        } catch (QueryException $exception) {
            $code = $exception->errorInfo[1] ?? null;
        }
        self::assertSame($expectedCode, $code);
    }
}
