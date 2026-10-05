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

use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\OperationVisibilityListener;
use Mandrael\ContaoBackendIconVisibilityBundle\Tests\FrameworkMockTrait;
use PHPUnit\Framework\TestCase;

class OperationVisibilityListenerTest extends TestCase
{
    use FrameworkMockTrait;

    protected function setUp(): void
    {
        $GLOBALS['TL_DCA'] = [];

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

    public function testMarksTheNewButtons(): void
    {
        $listener = new OperationVisibilityListener($this->mockFramework([
            'iconVisibilityPage' => serialize(['new']),
        ]));

        $listener('tl_page');

        $this->assertTrue($GLOBALS['TL_DCA']['tl_page']['list']['operations']['new']['primary']);
        $this->assertArrayNotHasKey('new', $GLOBALS['TL_DCA']['tl_news']['list']['operations']);
    }

    public function testShowsAllOperationsAndDisablesLazyLoading(): void
    {
        $listener = new OperationVisibilityListener($this->mockFramework([
            'iconVisibilityAll' => '1',
            'iconVisibilityDefault' => serialize(['show']),
        ]));

        $listener('tl_news');

        $this->assertSame(['edit', 'copy', 'cut', 'show', 'articles', 'new'], $this->primary('tl_news'));
        $this->assertFalse($GLOBALS['TL_DCA']['tl_news']['list']['lazyLoadOperations']);
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
