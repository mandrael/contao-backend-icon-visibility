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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Builds the grouped checkbox options of the selection fields: first the group
 * "In all lists", then one group per area with the operations that actually
 * exist in its lists, labelled like Contao labels them. Only areas whose back
 * end module is installed and accessible to the current user are offered.
 */
class SelectionOptionsListener
{
    /**
     * Contao's standard operations that are not primary by default.
     */
    private const ALL_LISTS_OPTIONS = ['copy', 'copyChildren', 'cut', 'delete', 'show', 'versions', OperationVisibilityListener::NEW];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly OperationVisibilityListener $visibility,
        private readonly Security $security,
    ) {
    }

    /**
     * @return array<string, array<string, string>>
     */
    #[AsCallback('tl_settings', 'fields.iconVisibilityShow.options')]
    #[AsCallback('tl_settings', 'fields.iconVisibilityMenu.options')]
    #[AsCallback('tl_user', 'fields.iconVisibilityShow.options')]
    #[AsCallback('tl_user', 'fields.iconVisibilityMenu.options')]
    public function getOptions(): array
    {
        $groups = [];
        $all = OperationVisibilityListener::ALL_LISTS;

        foreach (self::ALL_LISTS_OPTIONS as $key) {
            $groups[$this->areaLabel($all)]["$all:$key"] = $this->label(null, $key);
        }

        foreach (array_keys(OperationVisibilityListener::AREAS) as $area) {
            foreach ($this->getOptionsForArea($area) as $key => $label) {
                $groups[$this->areaLabel($area)]["$area:$key"] = $label;
            }
        }

        return $groups;
    }

    /**
     * @return array<string, string>
     */
    public function getOptionsForArea(string $area): array
    {
        if (!$this->isAccessible($area)) {
            return [];
        }

        $controller = $this->framework->getAdapter(Controller::class);
        $system = $this->framework->getAdapter(System::class);
        $options = [];

        foreach (OperationVisibilityListener::AREAS[$area]['tables'] as $table) {
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
            // so it is missing when the DCA is loaded outside the list.
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

    /**
     * The area's module must be installed (e.g. news, FAQ) and accessible.
     */
    private function isAccessible(string $area): bool
    {
        $installed = array_merge(...array_values(array_filter($GLOBALS['BE_MOD'] ?? [], 'is_array')));

        foreach (OperationVisibilityListener::AREAS[$area]['modules'] ?? [] as $module) {
            if (isset($installed[$module]) && $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, $module)) {
                return true;
            }
        }

        return false;
    }

    private function areaLabel(string $area): string
    {
        return $GLOBALS['TL_LANG']['MSC']['iconVisibilityAreas'][$area] ?? $area;
    }

    private function label(string|null $table, string $key, mixed $operationLabel = null): string
    {
        if (OperationVisibilityListener::NEW === $key) {
            $candidates = [$GLOBALS['TL_LANG']['MSC']['iconVisibilityNewOption'] ?? null];
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
