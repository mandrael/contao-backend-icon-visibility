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
use Contao\DC_Folder;
use Contao\DC_Table;
use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\OperationVisibilityListener;
use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\SettingsOptionsListener;
use Mandrael\ContaoBackendIconVisibilityBundle\Tests\FrameworkMockTrait;
use PHPUnit\Framework\TestCase;

class SettingsOptionsListenerTest extends TestCase
{
    use FrameworkMockTrait;

    private OperationVisibilityListener $visibility;

    private SettingsOptionsListener $listener;

    protected function setUp(): void
    {
        $GLOBALS['TL_DCA'] = [
            'tl_page' => [
                'config' => ['dataContainer' => DC_Table::class],
                'list' => [
                    'sorting' => ['mode' => DataContainer::MODE_TREE],
                    'operations' => [
                        'edit' => ['primary' => true],
                        'copy' => [],
                        '-',
                        'show' => [],
                        'articles' => [],
                    ],
                ],
            ],
            'tl_content' => [
                'config' => ['dataContainer' => DC_Table::class, 'dynamicPtable' => true],
                'list' => [
                    'sorting' => ['mode' => DataContainer::MODE_PARENT],
                    'operations' => ['edit' => ['primary' => true], 'copy' => [], 'delete' => []],
                ],
            ],
            'tl_files' => [
                'config' => ['dataContainer' => DC_Folder::class],
                'list' => [
                    'sorting' => ['mode' => DataContainer::MODE_TREE],
                    'operations' => ['edit' => ['primary' => true], 'source' => ['primary' => true], 'copy' => [], 'show' => []],
                ],
            ],
        ];

        $GLOBALS['TL_LANG'] = [
            'DCA' => ['copy' => ['Duplicate', 'Duplicate ID %s'], 'show' => 'Details', 'cut' => ['Move', 'Move ID %s'], 'delete' => ['Delete', 'Delete ID %s']],
            'tl_page' => ['articles' => ['Articles', 'Edit the articles of page ID %s'], 'copy' => ['Copy page', 'Copy page ID %s']],
            'tl_content' => ['cut' => 'Move element ID %s'],
            'tl_settings' => ['iconVisibilityNewOption' => 'New after/into'],
        ];

        $framework = $this->mockFramework();
        $this->visibility = new OperationVisibilityListener($framework);
        $this->listener = new SettingsOptionsListener($framework, $this->visibility);

        // Contao fires the hook while loading the DCA; the mocked loader does not.
        foreach (array_keys($GLOBALS['TL_DCA']) as $table) {
            ($this->visibility)($table);
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_LANG']);
    }

    public function testOffersOnlyExistingOperationsThatAreNotAlwaysVisible(): void
    {
        $this->assertSame(
            ['copy' => 'Copy page', 'show' => 'Details', 'articles' => 'Articles', 'new' => 'New after/into'],
            $this->listener->getOptionsForArea('iconVisibilityPage'),
        );
    }

    public function testAddsMoveForTablesWithADynamicParentTable(): void
    {
        $this->assertSame(
            ['copy' => 'Duplicate', 'delete' => 'Delete', 'cut' => 'Move', 'new' => 'New after/into'],
            $this->listener->getOptionsForArea('iconVisibilityContent'),
        );
    }

    public function testOffersNoNewButtonsInTheFileManager(): void
    {
        $this->assertSame(['copy' => 'Duplicate', 'show' => 'Details'], $this->listener->getOptionsForArea('iconVisibilityFiles'));
    }

    public function testReadsTheAreaFromTheDataContainer(): void
    {
        $dc = $this->createStub(DataContainer::class);
        $dc->method('__get')->willReturnMap([['field', 'iconVisibilityFiles']]);

        $this->assertSame(['copy' => 'Duplicate', 'show' => 'Details'], $this->listener->getAreaOptions($dc));
        $this->assertSame([], $this->listener->getAreaOptions(null));
    }

    public function testSkipsAreasWhoseTableDoesNotExist(): void
    {
        $this->assertSame([], $this->listener->getOptionsForArea('iconVisibilityNews'));
        $this->assertSame([], $this->listener->getOptionsForArea('unknownField'));
    }

    public function testDefaultOptionsFallBackToTheKey(): void
    {
        $this->assertSame(
            ['copy' => 'Duplicate', 'copyChildren' => 'copyChildren', 'cut' => 'Move', 'delete' => 'Delete', 'show' => 'Details', 'versions' => 'versions', 'new' => 'New after/into'],
            $this->listener->getDefaultOptions(),
        );
    }
}
