<?php

declare(strict_types=1);

/*
 * This file is part of mandrael/contao-backend-icon-visibility.
 *
 * (c) Michael Gasperl
 *
 * @license MIT
 */

namespace Mandrael\ContaoBackendIconVisibilityBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\DC_Folder;
use Contao\StringUtil;

/**
 * Marks the operations chosen in the system settings as "primary", so Contao
 * renders them as icons in the row instead of hiding them in the "..." menu.
 *
 * Runs after Contao's DefaultOperationsListener (priority 200), which adds the
 * default operations to every DCA.
 */
#[AsHook('loadDataContainer', priority: -100)]
class OperationVisibilityListener
{
    public const FIELD_ALL = 'iconVisibilityAll';

    public const FIELD_DEFAULT = 'iconVisibilityDefault';

    /**
     * Settings field per area => tables of that area.
     */
    public const AREAS = [
        'iconVisibilityPage' => ['tl_page'],
        'iconVisibilityArticle' => ['tl_article'],
        'iconVisibilityContent' => ['tl_content'],
        'iconVisibilityNews' => ['tl_news'],
        'iconVisibilityEvents' => ['tl_calendar_events'],
        'iconVisibilityFiles' => ['tl_files'],
        'iconVisibilityForm' => ['tl_form', 'tl_form_field'],
        'iconVisibilityMember' => ['tl_member'],
    ];

    /**
     * The "new after/into" buttons are not an operation of their own; Contao
     * reads their visibility from list.operations.new.primary.
     */
    public const NEW = 'new';

    /**
     * Operations Contao itself marks as primary, per table (always visible).
     *
     * @var array<string, list<string>>
     */
    private array $nativePrimary = [];

    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke(string $table): void
    {
        $operations = $GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null;

        if (!\is_array($operations)) {
            return;
        }

        $this->nativePrimary[$table] = array_map(
            'strval',
            array_keys(array_filter($operations, static fn ($operation): bool => \is_array($operation) && ($operation['primary'] ?? false))),
        );

        $config = $this->framework->getAdapter(Config::class);

        if ($config->get(self::FIELD_ALL)) {
            foreach ($operations as $key => $operation) {
                if (\is_array($operation)) {
                    $GLOBALS['TL_DCA'][$table]['list']['operations'][$key]['primary'] = true;
                }
            }

            $this->markNew($table);

            // Large lists would otherwise render only the primary operations and
            // lazy-load the rest into the "..." menu.
            $GLOBALS['TL_DCA'][$table]['list']['lazyLoadOperations'] = false;

            return;
        }

        $selected = $this->selection($config->get(self::FIELD_DEFAULT));

        foreach (self::AREAS as $field => $tables) {
            if (\in_array($table, $tables, true)) {
                $selected = [...$selected, ...$this->selection($config->get($field))];
            }
        }

        foreach (array_unique($selected) as $key) {
            if (self::NEW === $key) {
                $this->markNew($table);
            } elseif (\is_array($operations[$key] ?? null)) {
                $GLOBALS['TL_DCA'][$table]['list']['operations'][$key]['primary'] = true;
            }
        }
    }

    /**
     * @return list<string>
     */
    public function getNativePrimary(string $table): array
    {
        return $this->nativePrimary[$table] ?? [];
    }

    /**
     * Whether Contao renders "new after/into" buttons in the rows of this list
     * (see DC_Table::generateTree() and DC_Table::parentView()).
     *
     * @param array<mixed> $dca
     */
    public static function hasNewButtons(array $dca): bool
    {
        $config = $dca['config'] ?? [];

        if (
            DC_Folder::class === ($config['dataContainer'] ?? null)
            || ($config['closed'] ?? false)
            || ($config['notCreatable'] ?? false)
            || ($config['notEditable'] ?? false)
        ) {
            return false;
        }

        return match ($dca['list']['sorting']['mode'] ?? null) {
            DataContainer::MODE_TREE, DataContainer::MODE_TREE_EXTENDED => true,
            DataContainer::MODE_PARENT => 'sorting' === ($dca['list']['sorting']['fields'][0] ?? null),
            default => false,
        };
    }

    private function markNew(string $table): void
    {
        if (!self::hasNewButtons($GLOBALS['TL_DCA'][$table])) {
            return;
        }

        $new = $GLOBALS['TL_DCA'][$table]['list']['operations'][self::NEW] ?? null;

        if (null === $new || \is_array($new)) {
            $GLOBALS['TL_DCA'][$table]['list']['operations'][self::NEW]['primary'] = true;
        }
    }

    /**
     * @return list<string>
     */
    private function selection(mixed $value): array
    {
        return array_values(array_map('strval', StringUtil::deserialize($value, true)));
    }
}
