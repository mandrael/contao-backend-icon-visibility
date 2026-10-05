<?php

declare(strict_types=1);

/*
 * This file is part of mandrael/contao-backend-icon-visibility.
 *
 * (c) Michael Gasperl
 *
 * @license MIT
 */

namespace Mandrael\ContaoBackendIconVisibilityBundle\Tests;

use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\OperationVisibilityListener;
use PHPUnit\Framework\TestCase;

class LanguageFilesTest extends TestCase
{
    public function testAllLanguagesDefineTheSameLabels(): void
    {
        $de = $this->load('de');

        $this->assertSame(array_keys($de), array_keys($this->load('en')));

        $expected = [OperationVisibilityListener::FIELD_ALL, OperationVisibilityListener::FIELD_DEFAULT, ...array_keys(OperationVisibilityListener::AREAS)];

        foreach ($expected as $field) {
            $this->assertCount(2, $de[$field] ?? [], "Label for $field is missing");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function load(string $language): array
    {
        $GLOBALS['TL_LANG'] = [];
        require \dirname(__DIR__).'/contao/languages/'.$language.'/tl_settings.php';
        $labels = $GLOBALS['TL_LANG']['tl_settings'];
        unset($GLOBALS['TL_LANG']);

        return $labels;
    }
}
