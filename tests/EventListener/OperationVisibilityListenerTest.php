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

use Contao\DataContainer;
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

        (new OperationVisibilityListener($this->mockFramework()))('tl_page');

        $this->assertSame($before, $GLOBALS['TL_DCA']);
    }

    public function testCombinesTheGeneralAndTheAreaSelection(): void
    {
        $listener = new OperationVisibilityListener($this->mockFramework([
            'iconVisibilityDefault' => serialize(['show']),
            'iconVisibilityPage' => serialize(['articles', 'copy']),
        ]));

        $listener('tl_page');
        $listener('tl_news');

        $this->assertSame(['edit', 'copy', 'show', 'articles'], $this->primary('tl_page'));
        $this->assertSame(['edit', 'show'], $this->primary('tl_news'));
    }

    public function testIgnoresOperationsThatDoNotExistAndKeepsSeparators(): void
    {
        $listener = new OperationVisibilityListener($this->mockFramework([
            'iconVisibilityDefault' => serialize(['versions', 'copyChildren']),
        ]));

        $listener('tl_page');

        $operations = $GLOBALS['TL_DCA']['tl_page']['list']['operations'];

        $this->assertArrayNotHasKey('versions', $operations);
        $this->assertArrayNotHasKey('copyChildren', $operations);
        $this->assertSame('-', $operations[0]);
    }

    public function testMarksTheNewButtonsOnlyWhereContaoRendersThem(): void
    {
        $listener = new OperationVisibilityListener($this->mockFramework([
            'iconVisibilityDefault' => serialize(['new']),
        ]));

        $listener('tl_page');
        $listener('tl_news');

        $this->assertTrue($GLOBALS['TL_DCA']['tl_page']['list']['operations']['new']['primary']);

        // A parent view sorted by date has no "new after" buttons.
        $this->assertArrayNotHasKey('new', $GLOBALS['TL_DCA']['tl_news']['list']['operations']);
    }

    public function testDetectsListsWithNewButtons(): void
    {
        $this->assertTrue(OperationVisibilityListener::hasNewButtons(['list' => ['sorting' => ['mode' => DataContainer::MODE_TREE_EXTENDED]]]));
        $this->assertTrue(OperationVisibilityListener::hasNewButtons(['list' => ['sorting' => ['mode' => DataContainer::MODE_PARENT, 'fields' => ['sorting']]]]));
        $this->assertFalse(OperationVisibilityListener::hasNewButtons(['list' => ['sorting' => ['mode' => DataContainer::MODE_SORTABLE]]]));
        $this->assertFalse(OperationVisibilityListener::hasNewButtons(['config' => ['closed' => true], 'list' => ['sorting' => ['mode' => DataContainer::MODE_TREE]]]));
        $this->assertFalse(OperationVisibilityListener::hasNewButtons(['config' => ['dataContainer' => \Contao\DC_Folder::class], 'list' => ['sorting' => ['mode' => DataContainer::MODE_TREE]]]));
    }

    public function testShowsAllOperationsAndDisablesLazyLoading(): void
    {
        $listener = new OperationVisibilityListener($this->mockFramework([
            'iconVisibilityAll' => '1',
            'iconVisibilityDefault' => serialize(['show']),
        ]));

        $listener('tl_page');
        $listener('tl_news');

        $this->assertSame(['edit', 'copy', 'cut', 'show', 'articles', 'new'], $this->primary('tl_page'));
        $this->assertSame(['edit', 'copy', 'cut', 'show', 'articles'], $this->primary('tl_news'));
        $this->assertFalse($GLOBALS['TL_DCA']['tl_page']['list']['lazyLoadOperations']);
    }

    public function testRemembersTheOperationsContaoMarksAsPrimary(): void
    {
        $listener = new OperationVisibilityListener($this->mockFramework(['iconVisibilityAll' => '1']));

        $listener('tl_page');

        $this->assertSame(['edit'], $listener->getNativePrimary('tl_page'));
        $this->assertSame([], $listener->getNativePrimary('tl_unknown'));
    }

    public function testIgnoresTablesWithoutOperations(): void
    {
        $GLOBALS['TL_DCA']['tl_settings'] = ['config' => []];

        (new OperationVisibilityListener($this->mockFramework(['iconVisibilityAll' => '1'])))('tl_settings');

        $this->assertSame(['config' => []], $GLOBALS['TL_DCA']['tl_settings']);
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
