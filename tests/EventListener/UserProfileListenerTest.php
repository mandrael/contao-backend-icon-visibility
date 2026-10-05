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

    public function testHidesTheSelectionIfTheUserIsNotAllowed(): void
    {
        $GLOBALS['TL_DCA']['tl_user']['palettes'] = ['login' => self::PALETTE, 'default' => self::PALETTE];

        $this->listener(null)->hideIfNotAllowed();

        $this->assertSame('{name_legend},name;{backend_legend},language;{password_legend},password', $GLOBALS['TL_DCA']['tl_user']['palettes']['login']);
        $this->assertSame($GLOBALS['TL_DCA']['tl_user']['palettes']['login'], $GLOBALS['TL_DCA']['tl_user']['palettes']['default']);
    }

    public function testKeepsTheSelectionForAdministrators(): void
    {
        $GLOBALS['TL_DCA']['tl_user']['palettes'] = ['login' => self::PALETTE];

        $admin = $this->createStub(BackendUser::class);
        $admin->method('__get')->willReturnCallback(static fn (string $key): mixed => 'isAdmin' === $key);

        $this->listener(null, $admin)->hideIfNotAllowed();

        $this->assertSame(self::PALETTE, $GLOBALS['TL_DCA']['tl_user']['palettes']['login']);
    }

    public function testPrefillsANewSelectionWithTheSettings(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with('tl_user', ['iconVisibilityAll' => 1, 'iconVisibilityShow' => 'a:1:{i:0;s:8:"all:show";}', 'iconVisibilityMenu' => null], ['id' => 7])
        ;

        $listener = $this->listener($connection, null, ['iconVisibilityAll' => '1', 'iconVisibilityShow' => 'a:1:{i:0;s:8:"all:show";}']);

        $this->assertSame('1', $listener->prefill('1', $this->dc(['iconVisibilityOwn' => false, 'iconVisibilityAll' => false, 'iconVisibilityShow' => null])));
    }

    public function testKeepsAnExistingSelection(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('update');

        $listener = $this->listener($connection);

        $listener->prefill('1', $this->dc(['iconVisibilityOwn' => false, 'iconVisibilityShow' => serialize(['page:cut'])]));
        $listener->prefill('1', $this->dc(['iconVisibilityOwn' => true]));
        $listener->prefill('', $this->dc([]));
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
    private function dc(array $record): DataContainer
    {
        $dc = $this->createStub(DataContainer::class);
        $dc->method('getCurrentRecord')->willReturn($record);
        $dc->method('__get')->willReturnMap([['id', 7]]);

        return $dc;
    }
}
