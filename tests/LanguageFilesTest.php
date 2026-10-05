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
    public function testAllLanguagesDefineTheSameNonEmptyTexts(): void
    {
        $de = $this->load('de');
        $en = $this->load('en');

        $this->assertSame($this->keys($de), $this->keys($en));

        foreach (['de' => $de, 'en' => $en] as $language => $lang) {
            foreach ([OperationVisibilityListener::FIELD_ALL, OperationVisibilityListener::FIELD_SHOW, OperationVisibilityListener::FIELD_MENU] as $field) {
                $this->assertCount(2, $lang['MSC'][$field] ?? [], "$language: label for $field is missing");
            }

            $this->assertCount(2, $lang['tl_user'][OperationVisibilityListener::FIELD_OWN] ?? [], "$language: profile label is missing");
            $this->assertCount(2, $lang['tl_user_group'][OperationVisibilityListener::FIELD_ALLOW_OWN] ?? [], "$language: group label is missing");
            $this->assertSame([OperationVisibilityListener::ALL_LISTS, ...array_keys(OperationVisibilityListener::AREAS)], array_keys($lang['MSC']['iconVisibilityAreas']));

            array_walk_recursive($lang, fn (string $text, int|string $key) => $this->assertNotSame('', trim($text), "$language: empty text for $key"));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function load(string $language): array
    {
        $GLOBALS['TL_LANG'] = [];

        foreach (glob(\dirname(__DIR__).'/contao/languages/'.$language.'/*.php') ?: [] as $file) {
            require $file;
        }

        $lang = $GLOBALS['TL_LANG'];
        unset($GLOBALS['TL_LANG']);

        return $lang;
    }

    /**
     * @param array<mixed> $lang
     *
     * @return list<string>
     */
    private function keys(array $lang, string $prefix = ''): array
    {
        $keys = [];

        foreach ($lang as $key => $value) {
            $keys[] = $prefix.$key;

            if (\is_array($value) && !array_is_list($value)) {
                $keys = [...$keys, ...$this->keys($value, $prefix.$key.'.')];
            }
        }

        return $keys;
    }
}
