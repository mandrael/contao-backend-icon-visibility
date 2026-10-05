<?php

declare(strict_types=1);

/*
 * This file is part of mandrael/contao-backend-icon-visibility.
 *
 * (c) Michael Gasperl
 *
 * @license MIT
 */

namespace Mandrael\ContaoBackendIconVisibilityBundle\Tests\EventListener;

use Contao\BackendUser;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\UserProfileListener;
use Mandrael\ContaoBackendIconVisibilityBundle\Tests\FrameworkMockTrait;
use PHPUnit\Framework\TestCase;

class UserProfileListenerTest extends TestCase
{
    use FrameworkMockTrait;

    private const PALETTE = '{name_legend},name;{backend_legend},language;{icon_visibility_legend},iconVisibilityOwn;{password_legend},password';

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);
    }

    public function testLocksTheSelectionIfTheUserIsNotAllowed(): void
    {
        $GLOBALS['TL_DCA']['tl_user'] = [
            'palettes' => ['__selector__' => ['admin', 'iconVisibilityOwn'], 'login' => self::PALETTE, 'default' => self::PALETTE],
            'subpalettes' => ['iconVisibilityOwn' => 'iconVisibilityAll,iconVisibilityShow,iconVisibilityMenu'],
            'fields' => ['iconVisibilityOwn' => ['exclude' => false]],
        ];

        $this->listener(null)->lockIfNotAllowed();

        $dca = $GLOBALS['TL_DCA']['tl_user'];

        $this->assertSame('{name_legend},name;{backend_legend},language;{password_legend},password', $dca['palettes']['login']);
        $this->assertSame($dca['palettes']['login'], $dca['palettes']['default']);
        $this->assertSame(['admin'], $dca['palettes']['__selector__']);
        $this->assertSame([], $dca['subpalettes']);
        $this->assertTrue($dca['fields']['iconVisibilityOwn']['exclude']);
        $this->assertTrue($dca['fields']['iconVisibilityMenu']['exclude']);
    }

    public function testKeepsTheSelectionForAdministrators(): void
    {
        $GLOBALS['TL_DCA']['tl_user']['palettes'] = ['login' => self::PALETTE];

        $this->listener(null, $this->user(7, true))->lockIfNotAllowed();

        $this->assertSame(['login' => self::PALETTE], $GLOBALS['TL_DCA']['tl_user']['palettes']);
    }

    public function testPrefillsANewSelectionWithTheSettings(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with('tl_user', ['iconVisibilityAll' => 1, 'iconVisibilityShow' => 'a:1:{i:0;s:8:"all:show";}', 'iconVisibilityMenu' => ''], ['id' => 7])
        ;

        $listener = $this->listener($connection, $this->user(7, true), ['iconVisibilityAll' => '1', 'iconVisibilityShow' => 'a:1:{i:0;s:8:"all:show";}']);
        $listener->prefill($this->dc(7, ['iconVisibilityOwn' => true, 'iconVisibilityAll' => false, 'iconVisibilityShow' => null, 'iconVisibilityMenu' => null]));
    }

    public function testKeepsASavedSelectionEvenIfItIsEmpty(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('update');

        $listener = $this->listener($connection, $this->user(7, true));

        $listener->prefill($this->dc(7, ['iconVisibilityOwn' => true, 'iconVisibilityShow' => '', 'iconVisibilityMenu' => null]));
        $listener->prefill($this->dc(7, ['iconVisibilityOwn' => false, 'iconVisibilityShow' => null, 'iconVisibilityMenu' => null]));

        // Another user's record or a user without permission.
        $listener->prefill($this->dc(8, ['iconVisibilityOwn' => true, 'iconVisibilityShow' => null, 'iconVisibilityMenu' => null]));
        $this->listener($connection)->prefill($this->dc(7, ['iconVisibilityOwn' => true, 'iconVisibilityShow' => null, 'iconVisibilityMenu' => null]));
    }

    public function testStoresAnEmptySelectionAsAnEmptyList(): void
    {
        $listener = $this->listener(null);

        $this->assertSame('a:0:{}', $listener->keepEmptySelection(''));
        $this->assertSame('a:0:{}', $listener->keepEmptySelection(null));
        $this->assertSame('a:1:{i:0;s:8:"page:cut";}', $listener->keepEmptySelection('a:1:{i:0;s:8:"page:cut";}'));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function listener(Connection|null $connection, BackendUser|null $user = null, array $config = []): UserProfileListener
    {
        $connection ??= $this->createStub(Connection::class);

        return new UserProfileListener($this->mockFramework($config), $this->visibility([], $user, $connection), $this->mockSecurity($user), $connection);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function dc(int $id, array $record): DataContainer
    {
        $dc = $this->createStub(DataContainer::class);
        $dc->method('getCurrentRecord')->willReturn($record);
        $dc->method('__get')->willReturnMap([['id', $id]]);

        return $dc;
    }

    private function user(int $id, bool $admin): BackendUser
    {
        $user = $this->createStub(BackendUser::class);
        $user->method('__get')->willReturnCallback(static fn (string $key): mixed => ['id' => $id, 'isAdmin' => $admin][$key] ?? null);

        return $user;
    }
}
