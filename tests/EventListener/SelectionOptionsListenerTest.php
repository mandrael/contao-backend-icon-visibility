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

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\DC_Folder;
use Contao\DC_Table;
use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\OperationVisibilityListener;
use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\SelectionOptionsListener;
use Mandrael\ContaoBackendIconVisibilityBundle\Tests\FrameworkMockTrait;
use PHPUnit\Framework\TestCase;

class SelectionOptionsListenerTest extends TestCase
{
    use FrameworkMockTrait;

    private OperationVisibilityListener $visibility;

    private SelectionOptionsListener $listener;

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
                    'sorting' => ['mode' => DataContainer::MODE_PARENT, 'fields' => ['sorting']],
                    'operations' => ['edit' => ['primary' => true], 'copy' => [], 'delete' => []],
                ],
            ],
            'tl_news' => [
                'config' => ['dataContainer' => DC_Table::class],
                'list' => [
                    'sorting' => ['mode' => DataContainer::MODE_PARENT, 'fields' => ['date DESC']],
                    'operations' => ['edit' => ['primary' => true], 'copy' => [], 'cut' => []],
                ],
            ],
            'tl_form' => [
                'config' => ['dataContainer' => DC_Table::class],
                'list' => [
                    'sorting' => ['mode' => DataContainer::MODE_SORTABLE],
                    'operations' => ['edit' => ['primary' => true], 'copy' => [], 'show' => []],
                ],
            ],
            'tl_form_field' => [
                'config' => ['dataContainer' => DC_Table::class],
                'list' => [
                    'sorting' => ['mode' => DataContainer::MODE_PARENT, 'fields' => ['sorting']],
                    'operations' => ['edit' => ['primary' => true], 'copy' => [], 'cut' => []],
                ],
            ],
            'tl_member' => [
                'config' => ['dataContainer' => DC_Table::class],
                'list' => [
                    'sorting' => ['mode' => DataContainer::MODE_SORTABLE],
                    'operations' => ['edit' => ['primary' => true], 'sendReceipt' => ['label' => ['Send receipt', 'Send the receipt of member ID %s']]],
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
            'MSC' => ['iconVisibilityNewOption' => 'New after/into', 'iconVisibilityAreas' => ['all' => 'In all lists', 'page' => 'Pages', 'files' => 'Files']],
        ];

        $GLOBALS['BE_MOD'] = ['content' => ['page' => [], 'article' => [], 'news' => [], 'form' => [], 'files' => []], 'accounts' => ['member' => []]];

        $this->visibility = $this->visibility();
        $this->listener = new SelectionOptionsListener($this->mockFramework(), $this->visibility, $this->mockSecurity(null, ['page', 'article', 'news', 'form', 'files', 'member']));

        // Contao fires the hook while loading the DCA; the mocked loader does not.
        foreach (array_keys($GLOBALS['TL_DCA']) as $table) {
            ($this->visibility)($table);
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_LANG'], $GLOBALS['BE_MOD']);
    }

    public function testOffersOnlyExistingOperationsThatAreNotAlwaysVisible(): void
    {
        $this->assertSame(
            ['copy' => 'Copy page', 'show' => 'Details', 'articles' => 'Articles', 'new' => 'New after/into'],
            $this->listener->getOptionsForArea('page'),
        );
    }

    public function testAddsMoveForTablesWithADynamicParentTable(): void
    {
        $this->assertSame(
            ['copy' => 'Duplicate', 'delete' => 'Delete', 'cut' => 'Move', 'new' => 'New after/into'],
            $this->listener->getOptionsForArea('content'),
        );
    }

    public function testOmitsMoveIfTheTableIsNotSortable(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['config']['notSortable'] = true;

        $this->assertArrayNotHasKey('cut', $this->listener->getOptionsForArea('content'));
    }

    public function testOffersNoNewButtonsInTheFileManagerOrInListsSortedByDate(): void
    {
        $this->assertSame(['copy' => 'Duplicate', 'show' => 'Details'], $this->listener->getOptionsForArea('files'));
        $this->assertSame(['copy' => 'Duplicate', 'cut' => 'Move'], $this->listener->getOptionsForArea('news'));
    }

    public function testCombinesTheTablesOfAnArea(): void
    {
        $this->assertSame(
            ['copy' => 'Duplicate', 'show' => 'Details', 'cut' => 'Move', 'new' => 'New after/into'],
            $this->listener->getOptionsForArea('form'),
        );
    }

    public function testUsesTheLabelDefinedOnTheOperation(): void
    {
        $this->assertSame(['sendReceipt' => 'Send receipt'], $this->listener->getOptionsForArea('member'));
    }

    public function testSkipsAreasThatAreNotInstalledOrNotAccessible(): void
    {
        // The calendar module is not installed.
        $this->assertSame([], $this->listener->getOptionsForArea('events'));

        $listener = new SelectionOptionsListener($this->mockFramework(), $this->visibility, $this->mockSecurity(null, ['news']));

        $this->assertSame([], $listener->getOptionsForArea('page'));
        $this->assertSame([], $listener->getOptionsForArea('form'));

        // Content elements are reachable through news as well.
        $this->assertNotSame([], $listener->getOptionsForArea('content'));
    }

    public function testGroupsTheOptionsByArea(): void
    {
        $options = $this->listener->getOptions();

        $this->assertSame(['In all lists', 'Pages', 'content', 'news', 'form', 'Files', 'member'], array_keys($options));
        $this->assertSame(
            ['all:copy' => 'Duplicate', 'all:copyChildren' => 'copyChildren', 'all:cut' => 'Move', 'all:delete' => 'Delete', 'all:show' => 'Details', 'all:versions' => 'versions', 'all:new' => 'New after/into'],
            $options['In all lists'],
        );
        $this->assertSame(['files:copy' => 'Duplicate', 'files:show' => 'Details'], $options['Files']);
    }

    public function testShowsTheIconsInTheLabelOfTheOptIn(): void
    {
        $GLOBALS['TL_LANG']['MSC']['iconVisibilityNewIcons'] = ['Own icons: %s, %s', 'Help'];
        $GLOBALS['TL_DCA']['tl_settings'] = ['palettes' => ['default' => '{a},iconVisibilityAll,iconVisibilityNewIcons'], 'fields' => ['iconVisibilityNewIcons' => ['label' => &$GLOBALS['TL_LANG']['MSC']['iconVisibilityNewIcons']]]];

        $this->listener->addIconPreview();

        $this->assertSame(['Own icons: , ', 'Help'], $GLOBALS['TL_DCA']['tl_settings']['fields']['iconVisibilityNewIcons']['label']);
        $this->assertSame('Own icons: %s, %s', $GLOBALS['TL_LANG']['MSC']['iconVisibilityNewIcons'][0], 'the language string must stay unchanged');

        // A customized label with more placeholders than icons stays as it is.
        $GLOBALS['TL_LANG']['MSC']['iconVisibilityNewIcons'] = ['Own icons %s %s %s', 'Help'];
        $this->listener->addIconPreview();

        $this->assertSame('Own icons %s %s %s', $GLOBALS['TL_DCA']['tl_settings']['fields']['iconVisibilityNewIcons']['label'][0]);
    }

    public function testShowsNewAfterBeforeNewInto(): void
    {
        $GLOBALS['TL_LANG']['MSC']['iconVisibilityNewIcons'] = ['%s|%s', ''];
        $GLOBALS['TL_DCA']['tl_settings']['fields']['iconVisibilityNewIcons'] = [];

        $image = $this->createStub(Adapter::class);
        $image->method('__call')->willReturnCallback(static fn (string $method, array $args): string => $args[0]);
        $framework = $this->createStub(ContaoFramework::class);
        $framework->method('getAdapter')->willReturn($image);

        (new SelectionOptionsListener($framework, $this->visibility, $this->mockSecurity()))->addIconPreview();

        $this->assertSame(OperationVisibilityListener::NEW_ICONS['after'].'|'.OperationVisibilityListener::NEW_ICONS['into'], $GLOBALS['TL_DCA']['tl_settings']['fields']['iconVisibilityNewIcons']['label'][0]);
    }

    public function testRemovesTheOptInIfTheOwnIconsAreNotAvailable(): void
    {
        $GLOBALS['TL_DCA']['tl_settings'] = ['palettes' => ['default' => '{a},iconVisibilityAll,iconVisibilityNewIcons,iconVisibilityShow']];

        (new SelectionOptionsListener($this->mockFramework(), $this->visibility, $this->mockSecurity(), false))->addIconPreview();

        $this->assertSame('{a},iconVisibilityAll,iconVisibilityShow', $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']);
    }
}
