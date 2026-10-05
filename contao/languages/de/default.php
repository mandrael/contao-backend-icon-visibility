<?php

declare(strict_types=1);

/*
 * This file is part of mandrael/contao-backend-icon-visibility.
 *
 * (c) Michael Gasperl
 *
 * @license MIT
 */

$GLOBALS['TL_LANG']['MSC']['iconVisibilityAll'] = ['Alle Symbole anzeigen', 'Zeigt in allen Listen alle Symbole direkt in der Zeile, wie in Contao 5.3. „Direkt in der Zeile anzeigen“ ist dann ohne Wirkung, „Trotzdem im …-Menü lassen“ gilt weiter.'];
$GLOBALS['TL_LANG']['MSC']['iconVisibilityShow'] = ['Direkt in der Zeile anzeigen', '„In allen Listen“ gilt in jeder Liste, die das Symbol hat. Die Bereiche ergänzen die Auswahl für ihre Listen.'];
$GLOBALS['TL_LANG']['MSC']['iconVisibilityMenu'] = ['Trotzdem im „…“-Menü lassen', 'Hat Vorrang: Was hier angehakt ist, bleibt im Menü, auch wenn es oben oder mit „Alle Symbole anzeigen“ gewählt ist.'];
$GLOBALS['TL_LANG']['MSC']['iconVisibilityNewOption'] = 'Neu nach/in';
$GLOBALS['TL_LANG']['MSC']['iconVisibilityAreas'] = [
    'all' => 'In allen Listen',
    'page' => 'Seiten',
    'article' => 'Artikel',
    'content' => 'Inhaltselemente',
    'news' => 'Nachrichten',
    'events' => 'Events',
    'faq' => 'FAQ',
    'newsletter' => 'Newsletter und Abonnenten',
    'form' => 'Formulare und Formularfelder',
    'files' => 'Dateien',
    'member' => 'Mitglieder',
];
