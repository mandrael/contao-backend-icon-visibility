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
use Contao\System;
use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\OperationVisibilityListener;

$GLOBALS['TL_DCA']['tl_settings']['fields'][OperationVisibilityListener::FIELD_ALL] = [
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_settings']['fields'][OperationVisibilityListener::FIELD_DEFAULT] = [
    'inputType' => 'checkbox',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
];

// Areas whose bundle is not installed (e.g. news, calendar) are left out.
$bundles = System::getContainer()->getParameter('kernel.bundles');
$requiredBundles = [
    'iconVisibilityNews' => 'ContaoNewsBundle',
    'iconVisibilityEvents' => 'ContaoCalendarBundle',
];
$areaFields = [];

foreach (array_keys(OperationVisibilityListener::AREAS) as $field) {
    if (isset($requiredBundles[$field]) && !isset($bundles[$requiredBundles[$field]])) {
        continue;
    }

    $GLOBALS['TL_DCA']['tl_settings']['fields'][$field] = [
        'inputType' => 'checkbox',
        'eval' => ['multiple' => true, 'tl_class' => 'w50'],
    ];

    $areaFields[] = $field;
}

PaletteManipulator::create()
    ->addLegend('icon_visibility_legend', 'backend_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField([OperationVisibilityListener::FIELD_ALL, OperationVisibilityListener::FIELD_DEFAULT, ...$areaFields], 'icon_visibility_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_settings')
;

unset($bundles, $requiredBundles, $areaFields, $field);
