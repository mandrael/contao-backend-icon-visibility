<?php

declare(strict_types=1);

/*
 * This file is part of mandrael/contao-backend-icon-visibility.
 *
 * (c) Michael Gasperl
 *
 * @license MIT
 */

namespace Mandrael\ContaoBackendIconVisibilityBundle\DataContainer;

use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\OperationVisibilityListener;

/**
 * Contao shows "new after" and "new into" with the same icon, which is
 * ambiguous once both stand in the row. This builder gives them distinct icons
 * if this bundle made them primary and the DCA does not define its own icon.
 *
 * Replaces Contao's internal operations builder only if its signature matches
 * (see OperationsBuilderPass); otherwise Contao's icon remains.
 */
class OperationsBuilder extends DataContainerOperationsBuilder
{
    public const ICONS = [
        self::CREATE_AFTER => OperationVisibilityListener::NEW_ICONS['after'],
        self::CREATE_INTO => OperationVisibilityListener::NEW_ICONS['into'],
    ];

    public function addNewButton(string $mode, string $table, int $pid, int|null $id = null): DataContainerOperationsBuilder
    {
        $icon = self::iconFor($GLOBALS['TL_DCA'][$table]['list']['operations']['new'] ?? null, $mode);

        if (null === $icon) {
            return parent::addNewButton($mode, $table, $pid, $id);
        }

        $operation = &$GLOBALS['TL_DCA'][$table]['list']['operations']['new'];
        $hadIcon = \array_key_exists('icon', $operation);

        // Unset first, so a reference stored under "icon" is not overwritten.
        unset($operation['icon']);
        $operation['icon'] = $icon;

        try {
            return parent::addNewButton($mode, $table, $pid, $id);
        } finally {
            unset($operation['icon']);

            if ($hadIcon) {
                $operation['icon'] = null;
            }
        }
    }

    /**
     * The own icon if this bundle made the buttons primary and the DCA does
     * not define an icon itself.
     */
    public static function iconFor(mixed $new, string $mode): string|null
    {
        if (!\is_array($new) || !($new[OperationVisibilityListener::NEW_MARKER] ?? false) || !($new['primary'] ?? false) || isset($new['icon'])) {
            return null;
        }

        return self::ICONS[$mode] ?? null;
    }
}
