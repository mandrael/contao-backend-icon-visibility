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

// The own selection in the profile; same fields as in the system settings.
// Contao only unlocks the fields of the "login" palette on the profile page,
// so the subpalette fields are not excluded (they appear nowhere else).
$GLOBALS['TL_DCA']['tl_user']['fields'][OperationVisibilityListener::FIELD_OWN] = [
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_user']['fields'][OperationVisibilityListener::FIELD_ALL] = [
    'label' => &$GLOBALS['TL_LANG']['MSC'][OperationVisibilityListener::FIELD_ALL],
    'exclude' => false,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

foreach ([OperationVisibilityListener::FIELD_SHOW, OperationVisibilityListener::FIELD_MENU] as $field) {
    $GLOBALS['TL_DCA']['tl_user']['fields'][$field] = [
        'label' => &$GLOBALS['TL_LANG']['MSC'][$field],
        'exclude' => false,
        'inputType' => 'checkbox',
        'eval' => ['multiple' => true, 'collapseUncheckedGroups' => true, 'tl_class' => 'clr'],
        'sql' => ['type' => 'blob', 'length' => 65535, 'notnull' => false],
    ];
}

$GLOBALS['TL_DCA']['tl_user']['palettes']['__selector__'][] = OperationVisibilityListener::FIELD_OWN;
$GLOBALS['TL_DCA']['tl_user']['subpalettes'][OperationVisibilityListener::FIELD_OWN] = OperationVisibilityListener::FIELD_ALL.','.OperationVisibilityListener::FIELD_SHOW.','.OperationVisibilityListener::FIELD_MENU;

PaletteManipulator::create()
    ->addLegend('icon_visibility_legend', 'backend_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(OperationVisibilityListener::FIELD_OWN, 'icon_visibility_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('login', 'tl_user')
;

unset($field);
