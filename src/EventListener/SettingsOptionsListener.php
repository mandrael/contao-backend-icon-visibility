<?php

declare(strict_types=1);

/*
 * This file is part of mandrael/contao-backend-icon-visibility.
 *
 * (c) Michael Gasperl
 *
 * @license MIT
 */

namespace Mandrael\ContaoBackendIconVisibilityBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\System;

/**
 * Builds the checkbox options in the system settings from the operations that
 * actually exist in the respective list, labelled like Contao labels them.
 */
class SettingsOptionsListener
{
    /**
     * Contao's standard operations that are not primary by default.
     */
    private const DEFAULT_OPTIONS = ['copy', 'copyChildren', 'cut', 'delete', 'show', 'versions', OperationVisibilityListener::NEW];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly OperationVisibilityListener $visibility,
    ) {
    }

    /**
     * @return array<string, string>
     */
    #[AsCallback('tl_settings', 'fields.iconVisibilityDefault.options')]
    public function getDefaultOptions(): array
    {
        $options = [];

        foreach (self::DEFAULT_OPTIONS as $key) {
            $options[$key] = $this->label(null, $key);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    #[AsCallback('tl_settings', 'fields.iconVisibilityPage.options')]
    #[AsCallback('tl_settings', 'fields.iconVisibilityArticle.options')]
    #[AsCallback('tl_settings', 'fields.iconVisibilityContent.options')]
    #[AsCallback('tl_settings', 'fields.iconVisibilityNews.options')]
    #[AsCallback('tl_settings', 'fields.iconVisibilityEvents.options')]
    #[AsCallback('tl_settings', 'fields.iconVisibilityFiles.options')]
    #[AsCallback('tl_settings', 'fields.iconVisibilityForm.options')]
    #[AsCallback('tl_settings', 'fields.iconVisibilityMember.options')]
    public function getAreaOptions(DataContainer|null $dc = null): array
    {
        return $this->getOptionsForArea((string) $dc?->field);
    }

    /**
     * @return array<string, string>
     */
    public function getOptionsForArea(string $field): array
    {
        $controller = $this->framework->getAdapter(Controller::class);
        $system = $this->framework->getAdapter(System::class);
        $options = [];

        foreach (OperationVisibilityListener::AREAS[$field] ?? [] as $table) {
            $controller->loadDataContainer($table);
            $system->loadLanguageFile($table);

            $dca = $GLOBALS['TL_DCA'][$table] ?? null;

            if (!\is_array($dca)) {
                continue;
            }

            $native = $this->visibility->getNativePrimary($table);
            $operations = [];

            foreach ($dca['list']['operations'] ?? [] as $key => $operation) {
                if (\is_array($operation) && OperationVisibilityListener::NEW !== $key) {
                    $operations[(string) $key] = $operation;
                }
            }

            // The parent table (and therefore "move") is only set at runtime,
            // so it is missing when the DCA is loaded in the system settings.
            if (($dca['config']['dynamicPtable'] ?? false) && !($dca['config']['notSortable'] ?? false)) {
                $operations['cut'] ??= [];
            }

            if (OperationVisibilityListener::hasNewButtons($dca)) {
                $operations[OperationVisibilityListener::NEW] = [];
            }

            foreach ($operations as $key => $operation) {
                if (!isset($options[$key]) && !\in_array($key, $native, true)) {
                    $options[$key] = $this->label($table, $key, $operation['label'] ?? null);
                }
            }
        }

        return $options;
    }

    private function label(string|null $table, string $key, mixed $operationLabel = null): string
    {
        if (OperationVisibilityListener::NEW === $key) {
            $candidates = [$GLOBALS['TL_LANG']['tl_settings']['iconVisibilityNewOption'] ?? null];
        } else {
            $tableLabel = null !== $table ? $GLOBALS['TL_LANG'][$table][$key] ?? null : null;
            $dcaLabel = $GLOBALS['TL_LANG']['DCA'][$key] ?? null;

            // Short labels first (a label defined on the operation wins, as in
            // Contao), then the long ones, which are titles for a single record.
            $candidates = [
                \is_array($operationLabel) ? $operationLabel[0] ?? null : null,
                \is_array($tableLabel) ? $tableLabel[0] ?? null : null,
                \is_array($dcaLabel) ? $dcaLabel[0] ?? null : $dcaLabel,
                \is_array($operationLabel) ? $operationLabel[1] ?? null : $operationLabel,
                \is_array($tableLabel) ? $tableLabel[1] ?? null : $tableLabel,
            ];
        }

        $label = null;

        foreach ($candidates as $candidate) {
            if (\is_string($candidate) && '' !== trim($candidate)) {
                $label = $candidate;
                break;
            }
        }

        if (null === $label) {
            return $key;
        }

        // Titles such as "Edit page ID %s" are meant for a single record.
        return trim(preg_replace('/\s*(ID\s*)?%s/', '', $label) ?? $label);
    }
}
