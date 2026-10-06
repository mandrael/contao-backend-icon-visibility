<?php

declare(strict_types=1);

/*
 * This file is part of mandrael/contao-backend-icon-visibility.
 *
 * (c) Michael Gasperl
 *
 * @license MIT
 */

$GLOBALS['TL_LANG']['MSC']['iconVisibilityAll'] = ['Show all icons', 'Shows all icons directly in the row of every list, as in Contao 5.3. "Show in the row" then has no effect, "Keep in the … menu" still applies.'];
$GLOBALS['TL_LANG']['MSC']['iconVisibilityNewIcons'] = ['Own icons: new after %s, new into %s', 'Contao shows both with the same icon. This tells them apart in the row.'];
$GLOBALS['TL_LANG']['MSC']['iconVisibilityShow'] = ['Show in the row', '"In all lists" applies to every list that has the icon. The areas add to the selection for their lists.'];
$GLOBALS['TL_LANG']['MSC']['iconVisibilityMenu'] = ['Keep in the "…" menu', 'Takes precedence: what is checked here stays in the menu, even if it is selected above or with "Show all icons".'];
$GLOBALS['TL_LANG']['MSC']['iconVisibilityNewOption'] = 'New after/into';
$GLOBALS['TL_LANG']['MSC']['iconVisibilityAreas'] = [
    'all' => 'In all lists',
    'page' => 'Pages',
    'article' => 'Articles',
    'content' => 'Content elements',
    'news' => 'News',
    'events' => 'Events',
    'faq' => 'FAQ',
    'newsletter' => 'Newsletters and recipients',
    'form' => 'Forms and form fields',
    'files' => 'Files',
    'member' => 'Members',
];
