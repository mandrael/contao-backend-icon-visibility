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

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\DC_Folder;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Marks the operations chosen in the system settings (or in the user's own
 * selection) as "primary", so Contao renders them as icons in the row instead
 * of hiding them in the "..." menu.
 *
 * Runs after Contao's DefaultOperationsListener (priority 200), which adds the
 * default operations to every DCA.
 */
#[AsHook('loadDataContainer', priority: -100)]
class OperationVisibilityListener implements ResetInterface
{
    public const FIELD_ALL = 'iconVisibilityAll';

    public const FIELD_SHOW = 'iconVisibilityShow';

    public const FIELD_MENU = 'iconVisibilityMenu';

    /**
     * Profile switch: use the user's own selection instead of the settings.
     */
    public const FIELD_OWN = 'iconVisibilityOwn';

    /**
     * User group permission to use an own selection.
     */
    public const FIELD_ALLOW_OWN = 'iconVisibilityAllowOwn';

    /**
     * Pseudo area of the selection "In all lists".
     */
    public const ALL_LISTS = 'all';

    /**
     * Area => back end modules that lead to it and tables of its lists.
     * Values are stored as "area:operation", e.g. "page:copy" or "all:show".
     */
    public const AREAS = [
        'page' => ['modules' => ['page'], 'tables' => ['tl_page']],
        'article' => ['modules' => ['article'], 'tables' => ['tl_article']],
        'content' => ['modules' => ['article', 'news', 'calendar'], 'tables' => ['tl_content']],
        'news' => ['modules' => ['news'], 'tables' => ['tl_news']],
        'events' => ['modules' => ['calendar'], 'tables' => ['tl_calendar_events']],
        'faq' => ['modules' => ['faq'], 'tables' => ['tl_faq']],
        'newsletter' => ['modules' => ['newsletter'], 'tables' => ['tl_newsletter', 'tl_newsletter_recipients']],
        'form' => ['modules' => ['form'], 'tables' => ['tl_form', 'tl_form_field']],
        'files' => ['modules' => ['files'], 'tables' => ['tl_files']],
        'member' => ['modules' => ['member'], 'tables' => ['tl_member']],
    ];

    /**
     * The "new after/into" buttons are not an operation of their own; Contao
     * reads their visibility from list.operations.new.primary.
     */
    public const NEW = 'new';

    /**
     * Set on list.operations.new when this bundle made the buttons primary, so
     * they get distinct icons (see DataContainer\OperationsBuilder).
     */
    public const NEW_MARKER = 'iconVisibilityMarked';

    /**
     * Operations Contao itself marks as primary, per table (always visible).
     *
     * @var array<string, list<string>>
     */
    private array $nativePrimary = [];

    /**
     * @var array<int, bool>
     */
    private array $allowOwn = [];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly Connection $connection,
    ) {
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

        [$all, $show, $menu] = $this->selection();

        $areas = [self::ALL_LISTS];

        foreach (self::AREAS as $area => $config) {
            if (\in_array($table, $config['tables'], true)) {
                $areas[] = $area;
            }
        }

        $hidden = $this->operationsOf($menu, $areas);
        $visible = $all ? [...array_map('strval', array_keys($operations)), self::NEW] : $this->operationsOf($show, $areas);

        foreach (array_diff(array_unique($visible), $hidden) as $key) {
            if (self::NEW === $key) {
                $this->markNew($table);
            } elseif (\is_array($operations[$key] ?? null)) {
                $GLOBALS['TL_DCA'][$table]['list']['operations'][$key]['primary'] = true;
            }
        }

        // Large lists would otherwise render only the primary operations and
        // lazy-load the rest into the "..." menu.
        if ($all) {
            $GLOBALS['TL_DCA'][$table]['list']['lazyLoadOperations'] = false;
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
     * Whether the user may replace the settings with an own selection:
     * administrators always, others if one of their active groups allows it.
     */
    public function allowsOwnSelection(BackendUser $user): bool
    {
        if ($user->isAdmin) {
            return true;
        }

        $groups = array_map('intval', array_filter((array) $user->groups));

        if (!$groups) {
            return false;
        }

        // Same conditions as BackendUser::setUserFromDb() for active groups.
        $time = time() - time() % 60;

        return $this->allowOwn[(int) $user->id] ??= (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM tl_user_group WHERE id IN (:ids) AND disable = 0 AND (start = '' OR start <= :time) AND (stop = '' OR stop > :time) AND ".self::FIELD_ALLOW_OWN.' = 1',
            ['ids' => $groups, 'time' => $time],
            ['ids' => ArrayParameterType::INTEGER],
        );
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

    public function reset(): void
    {
        $this->nativePrimary = [];
        $this->allowOwn = [];
    }

    /**
     * The user's own selection if enabled and allowed, otherwise the settings.
     *
     * @return array{bool, list<string>, list<string>}
     */
    private function selection(): array
    {
        $user = $this->security->getUser();

        if ($user instanceof BackendUser && $user->__get(self::FIELD_OWN) && $this->allowsOwnSelection($user)) {
            $get = $user->__get(...);
        } else {
            $config = $this->framework->getAdapter(Config::class);
            $get = static fn (string $field): mixed => $config->get($field);
        }

        return [(bool) $get(self::FIELD_ALL), $this->values($get(self::FIELD_SHOW)), $this->values($get(self::FIELD_MENU))];
    }

    /**
     * @param list<string> $values
     * @param list<string> $areas
     *
     * @return list<string>
     */
    private function operationsOf(array $values, array $areas): array
    {
        $operations = [];

        foreach ($values as $value) {
            [$area, $operation] = explode(':', $value, 2) + [1 => ''];

            if ('' !== $operation && \in_array($area, $areas, true)) {
                $operations[] = $operation;
            }
        }

        return $operations;
    }

    private function markNew(string $table): void
    {
        if (!self::hasNewButtons($GLOBALS['TL_DCA'][$table])) {
            return;
        }

        $new = $GLOBALS['TL_DCA'][$table]['list']['operations'][self::NEW] ?? null;

        if (null === $new || \is_array($new)) {
            $GLOBALS['TL_DCA'][$table]['list']['operations'][self::NEW]['primary'] = true;
            $GLOBALS['TL_DCA'][$table]['list']['operations'][self::NEW][self::NEW_MARKER] = true;
        }
    }

    /**
     * @return list<string>
     */
    private function values(mixed $value): array
    {
        return array_values(array_map('strval', StringUtil::deserialize($value, true)));
    }
}
