<?php

declare(strict_types=1);

/*
 * This file is part of mandrael/contao-backend-icon-visibility.
 *
 * (c) Michael Gasperl
 *
 * @license MIT
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\OperationVisibilityListener;

$GLOBALS['TL_DCA']['tl_settings']['fields'][OperationVisibilityListener::FIELD_ALL] = [
    'label' => &$GLOBALS['TL_LANG']['MSC'][OperationVisibilityListener::FIELD_ALL],
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr'],
];

foreach ([OperationVisibilityListener::FIELD_SHOW, OperationVisibilityListener::FIELD_MENU] as $field) {
    $GLOBALS['TL_DCA']['tl_settings']['fields'][$field] = [
        'label' => &$GLOBALS['TL_LANG']['MSC'][$field],
        'inputType' => 'checkbox',
        'eval' => ['multiple' => true, 'collapseUncheckedGroups' => true, 'tl_class' => 'clr'],
    ];
}

PaletteManipulator::create()
    ->addLegend('icon_visibility_legend', 'backend_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField([OperationVisibilityListener::FIELD_ALL, OperationVisibilityListener::FIELD_SHOW, OperationVisibilityListener::FIELD_MENU], 'icon_visibility_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_settings')
;

unset($field);
