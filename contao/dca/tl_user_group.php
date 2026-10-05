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

$GLOBALS['TL_DCA']['tl_user_group']['fields'][OperationVisibilityListener::FIELD_ALLOW_OWN] = [
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

PaletteManipulator::create()
    ->addLegend('icon_visibility_legend', 'alexf_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(OperationVisibilityListener::FIELD_ALLOW_OWN, 'icon_visibility_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group')
;
