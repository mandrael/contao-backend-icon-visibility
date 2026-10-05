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

/**
 * Contao shows "new after" and "new into" with the same icon, which is
 * ambiguous once both stand in the row. This builder gives them distinct icons
 * if the bundle made them primary and the DCA does not define its own icon.
 *
 * Replaces Contao's internal operations builder only if its signature matches
 * (see OperationsBuilderPass); otherwise Contao's icon remains.
 */
class OperationsBuilder extends DataContainerOperationsBuilder
{
    public const ICONS = [
        self::CREATE_AFTER => 'bundles/mandraelcontaobackendiconvisibility/icons/new-after.svg',
        self::CREATE_INTO => 'bundles/mandraelcontaobackendiconvisibility/icons/new-into.svg',
    ];

    public function addNewButton(string $mode, string $table, int $pid, int|null $id = null): DataContainerOperationsBuilder
    {
        $new = $GLOBALS['TL_DCA'][$table]['list']['operations']['new'] ?? null;

        if (!isset(self::ICONS[$mode]) || !\is_array($new) || !($new['primary'] ?? false) || isset($new['icon'])) {
            return parent::addNewButton($mode, $table, $pid, $id);
        }

        $GLOBALS['TL_DCA'][$table]['list']['operations']['new']['icon'] = self::ICONS[$mode];

        try {
            return parent::addNewButton($mode, $table, $pid, $id);
        } finally {
            unset($GLOBALS['TL_DCA'][$table]['list']['operations']['new']['icon']);
        }
    }
}
