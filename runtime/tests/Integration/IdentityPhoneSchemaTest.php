<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversFile(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Migrations/2026_10_08_000004_create_phones_table.php')]
#[CoversClass(\App\Shared\Infrastructure\MysqlChecks::class)]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class IdentityPhoneSchemaTest extends DatabaseTestCase
{
    public function testCanonicalNumbersAreUniqueAndOnlyOneActiveMainPhoneIsAllowed(): void
    {
        $db = $this->database->connection();
        $user = $this->createUserId();
        $other = $this->createUserId();
        $phone = ['user_id' => $user, 'main' => true, 'label' => 'mobile', 'country_code' => 'US', 'number' => '+15550100001'];
        $first = $db->table('phones')->insertGetId($phone);
        $this->assertSqlRejected(fn () => $db->table('phones')->insert(array_replace($phone, ['number' => '+15550100002'])), 1062);
        $this->assertSqlRejected(fn () => $db->table('phones')->insert(array_replace($phone, ['user_id' => $other])), 1062);
        $db->table('phones')->insert(array_replace($phone, ['main' => false, 'number' => '+15550100002']));
        $db->table('phones')->where('id', $first)->update(['deleted_at' => now()]);
        $db->table('phones')->insert(array_replace($phone, ['number' => '+15550100003']));
        $this->assertSqlRejected(fn () => $db->table('phones')->where('id', $first)->update(['deleted_at' => null]), 1062);
        self::assertSame(2, $db->table('phones')->whereNull('deleted_at')->count());
    }

    public function testPhoneFormatCountryAndBooleanAreDatabaseInvariants(): void
    {
        $db = $this->database->connection();
        $row = ['user_id' => $this->createUserId(), 'main' => true, 'label' => 'mobile', 'country_code' => 'US', 'number' => '+15550100001'];
        foreach ([['number' => '15550100001'], ['number' => '+05550100001'], ['number' => '+1'], ['number' => "+19990000001\n"], ['country_code' => 'us'], ['country_code' => '1A'], ['main' => 2]] as $invalid) {
            $this->assertSqlRejected(fn () => $db->table('phones')->insert(array_replace($row, $invalid)), 3819);
        }
        $this->assertSqlRejected(fn () => $db->table('phones')->insert(array_replace($row, ['user_id' => 999999])), 1452);
        self::assertSame(0, $db->table('phones')->count());
    }

    public function testPhonesParticipateInTransactionsAndCascadeOnlyTheirOwner(): void
    {
        $db = $this->database->connection();
        $first = $this->createUserId();
        $second = $this->createUserId();
        $db->table('phones')->insert([
            ['user_id' => $first, 'main' => true, 'label' => 'mobile', 'country_code' => 'US', 'number' => '+15550100001'],
            ['user_id' => $second, 'main' => true, 'label' => 'mobile', 'country_code' => 'US', 'number' => '+15550100002'],
        ]);
        $db->beginTransaction();
        try {
            $db->table('users')->where('id', $first)->delete();
            self::assertSame(1, $db->table('phones')->count());
            $db->rollBack();
            self::assertSame(2, $db->table('phones')->count());
        } finally {
            if ($db->transactionLevel() > 0) {
                $db->rollBack();
            }
        }
        $db->table('users')->where('id', $first)->delete();
        self::assertSame($second, $db->table('phones')->value('user_id'));
    }

    public function testPhoneMigrationCanBeRolledBackAndReapplied(): void
    {
        $db = $this->database->connection();
        $migration = require __DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Migrations/2026_10_08_000004_create_phones_table.php';
        $migration->down();
        self::assertFalse($db->getSchemaBuilder()->hasTable('phones'));
        $migration->up();
        self::assertTrue($db->getSchemaBuilder()->hasTable('phones'));
    }
}
