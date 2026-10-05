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
use Contao\DC_Folder;
use Doctrine\DBAL\Connection;
use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\OperationVisibilityListener;
use Mandrael\ContaoBackendIconVisibilityBundle\Tests\FrameworkMockTrait;
use PHPUnit\Framework\TestCase;

class OperationVisibilityListenerTest extends TestCase
{
    use FrameworkMockTrait;

    protected function setUp(): void
    {
        $GLOBALS['TL_DCA'] = [];

        $GLOBALS['TL_DCA']['tl_page']['list']['sorting'] = ['mode' => DataContainer::MODE_TREE];
        $GLOBALS['TL_DCA']['tl_news']['list']['sorting'] = ['mode' => DataContainer::MODE_PARENT, 'fields' => ['date DESC']];

        foreach (['tl_page', 'tl_news'] as $table) {
            $GLOBALS['TL_DCA'][$table]['list']['operations'] = [
                'edit' => ['icon' => 'edit.svg', 'primary' => true],
                'copy' => ['icon' => 'copy.svg'],
                'cut' => ['icon' => 'cut.svg'],
                '-',
                'show' => ['icon' => 'show.svg'],
                'articles' => ['icon' => 'article.svg'],
            ];
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);
    }

    public function testLeavesTheDcaUnchangedWithoutSettings(): void
    {
        $before = $GLOBALS['TL_DCA'];

        ($this->visibility())('tl_page');

        $this->assertSame($before, $GLOBALS['TL_DCA']);
    }

    public function testCombinesAllListsAndTheArea(): void
    {
        $listener = $this->visibility(['iconVisibilityShow' => serialize(['all:show', 'page:articles', 'page:copy', 'news:cut'])]);

        $listener('tl_page');
        $listener('tl_news');

        $this->assertSame(['edit', 'copy', 'show', 'articles'], $this->primary('tl_page'));
        $this->assertSame(['edit', 'cut', 'show'], $this->primary('tl_news'));
    }

    public function testTheMenuSelectionWins(): void
    {
        $listener = $this->visibility([
            'iconVisibilityShow' => serialize(['all:show', 'all:copy']),
            'iconVisibilityMenu' => serialize(['news:show', 'all:copy']),
        ]);

        $listener('tl_page');
        $listener('tl_news');

        $this->assertSame(['edit', 'show'], $this->primary('tl_page'));
        $this->assertSame(['edit'], $this->primary('tl_news'));
    }

    public function testShowsAllOperationsExceptTheMenuSelection(): void
    {
        $listener = $this->visibility([
            'iconVisibilityAll' => '1',
            'iconVisibilityMenu' => serialize(['news:cut', 'page:new']),
        ]);

        $listener('tl_page');
        $listener('tl_news');

        $this->assertSame(['edit', 'copy', 'cut', 'show', 'articles'], $this->primary('tl_page'));
        $this->assertArrayNotHasKey('new', $GLOBALS['TL_DCA']['tl_page']['list']['operations']);
        $this->assertSame(['edit', 'copy', 'show', 'articles'], $this->primary('tl_news'));
        $this->assertFalse($GLOBALS['TL_DCA']['tl_page']['list']['lazyLoadOperations']);
    }

    public function testIgnoresOperationsThatDoNotExistAndKeepsSeparators(): void
    {
        ($this->visibility(['iconVisibilityShow' => serialize(['all:versions', 'all:copyChildren', 'broken', 'page:'])]))('tl_page');

        $operations = $GLOBALS['TL_DCA']['tl_page']['list']['operations'];

        $this->assertArrayNotHasKey('versions', $operations);
        $this->assertArrayNotHasKey('copyChildren', $operations);
        $this->assertSame('-', $operations[0]);
        $this->assertSame(['edit'], $this->primary('tl_page'));
    }

    public function testMarksTheNewButtonsOnlyWhereContaoRendersThem(): void
    {
        $listener = $this->visibility(['iconVisibilityShow' => serialize(['all:new'])]);

        $listener('tl_page');
        $listener('tl_news');

        $this->assertTrue($GLOBALS['TL_DCA']['tl_page']['list']['operations']['new']['primary']);
        $this->assertTrue($GLOBALS['TL_DCA']['tl_page']['list']['operations']['new'][OperationVisibilityListener::NEW_MARKER]);

        // A parent view sorted by date has no "new after" buttons.
        $this->assertArrayNotHasKey('new', $GLOBALS['TL_DCA']['tl_news']['list']['operations']);
    }

    public function testDetectsListsWithNewButtons(): void
    {
        $this->assertTrue(OperationVisibilityListener::hasNewButtons(['list' => ['sorting' => ['mode' => DataContainer::MODE_TREE_EXTENDED]]]));
        $this->assertTrue(OperationVisibilityListener::hasNewButtons(['list' => ['sorting' => ['mode' => DataContainer::MODE_PARENT, 'fields' => ['sorting']]]]));
        $this->assertFalse(OperationVisibilityListener::hasNewButtons(['list' => ['sorting' => ['mode' => DataContainer::MODE_SORTABLE]]]));
        $this->assertFalse(OperationVisibilityListener::hasNewButtons(['config' => ['closed' => true], 'list' => ['sorting' => ['mode' => DataContainer::MODE_TREE]]]));
        $this->assertFalse(OperationVisibilityListener::hasNewButtons(['config' => ['dataContainer' => DC_Folder::class], 'list' => ['sorting' => ['mode' => DataContainer::MODE_TREE]]]));
    }

    public function testUsesTheOwnSelectionOfAnAllowedUser(): void
    {
        $user = $this->user(['iconVisibilityOwn' => true, 'iconVisibilityShow' => ['page:cut'], 'groups' => [3]]);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with($this->logicalAnd($this->stringContains('disable = 0'), $this->stringContains("start = '' OR start <= :time"), $this->stringContains('stop > :time')))
            ->willReturn(1)
        ;

        $listener = $this->visibility(['iconVisibilityAll' => '1'], $user, $connection);
        $listener('tl_page');
        $listener('tl_news');

        // The own selection replaces the settings ("show all" is not inherited).
        $this->assertSame(['edit', 'cut'], $this->primary('tl_page'));
        $this->assertSame(['edit'], $this->primary('tl_news'));
    }

    public function testFallsBackToTheSettingsIfTheUserIsNotAllowed(): void
    {
        $user = $this->user(['iconVisibilityOwn' => true, 'iconVisibilityShow' => ['page:cut'], 'groups' => [3]]);

        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturn(0);

        ($this->visibility(['iconVisibilityShow' => serialize(['all:show'])], $user, $connection))('tl_page');

        $this->assertSame(['edit', 'show'], $this->primary('tl_page'));
    }

    public function testAdministratorsAndOnlyAllowedGroupsMayUseAnOwnSelection(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('fetchOne');

        $listener = $this->visibility([], null, $connection);

        $this->assertTrue($listener->allowsOwnSelection($this->user(['isAdmin' => true])));
        $this->assertFalse($listener->allowsOwnSelection($this->user(['groups' => []])));
    }

    public function testRemembersTheOperationsContaoMarksAsPrimary(): void
    {
        $listener = $this->visibility(['iconVisibilityAll' => '1']);

        $listener('tl_page');

        $this->assertSame(['edit'], $listener->getNativePrimary('tl_page'));
        $this->assertSame([], $listener->getNativePrimary('tl_unknown'));

        $listener->reset();

        $this->assertSame([], $listener->getNativePrimary('tl_page'));
    }

    public function testIgnoresTablesWithoutOperations(): void
    {
        $GLOBALS['TL_DCA']['tl_settings'] = ['config' => []];

        ($this->visibility(['iconVisibilityAll' => '1']))('tl_settings');

        $this->assertSame(['config' => []], $GLOBALS['TL_DCA']['tl_settings']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function user(array $data): BackendUser
    {
        $user = $this->createStub(BackendUser::class);
        $user->method('__get')->willReturnCallback(static fn (string $key): mixed => $data[$key] ?? null);

        return $user;
    }

    /**
     * @return list<string>
     */
    private function primary(string $table): array
    {
        $keys = [];

        foreach ($GLOBALS['TL_DCA'][$table]['list']['operations'] as $key => $operation) {
            if (\is_array($operation) && ($operation['primary'] ?? false)) {
                $keys[] = (string) $key;
            }
        }

        return $keys;
    }
}
