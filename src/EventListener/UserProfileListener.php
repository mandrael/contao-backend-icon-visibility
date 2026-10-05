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

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * The own selection in the user profile: locked unless a user group allows it,
 * prefilled with the system settings when the user switches it on.
 */
class UserProfileListener
{
    public const LEGEND = '{icon_visibility_legend},'.OperationVisibilityListener::FIELD_OWN;

    private const SELECTION_FIELDS = [OperationVisibilityListener::FIELD_ALL, OperationVisibilityListener::FIELD_SHOW, OperationVisibilityListener::FIELD_MENU];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly OperationVisibilityListener $visibility,
        private readonly Security $security,
        private readonly Connection $connection,
    ) {
    }

    #[AsCallback('tl_user', 'config.onload')]
    public function lockIfNotAllowed(): void
    {
        if ($this->allowedUser()) {
            return;
        }

        $dca = &$GLOBALS['TL_DCA']['tl_user'];

        // Contao copies the "login" palette to "default" on the profile page.
        foreach (['login', 'default'] as $palette) {
            if (isset($dca['palettes'][$palette])) {
                $dca['palettes'][$palette] = str_replace(';'.self::LEGEND, '', $dca['palettes'][$palette]);
            }
        }

        // Also reject the Ajax request that toggles the subpalette.
        $dca['palettes']['__selector__'] = array_values(array_diff($dca['palettes']['__selector__'] ?? [], [OperationVisibilityListener::FIELD_OWN]));
        unset($dca['subpalettes'][OperationVisibilityListener::FIELD_OWN]);

        foreach ([OperationVisibilityListener::FIELD_OWN, ...self::SELECTION_FIELDS] as $field) {
            $dca['fields'][$field]['exclude'] = true;
        }
    }

    /**
     * Runs after the record was saved (also when the switch is toggled via
     * Ajax), so an invalid form does not write anything.
     */
    #[AsCallback('tl_user', 'config.onsubmit')]
    public function prefill(DataContainer $dc): void
    {
        $user = $this->allowedUser();
        $record = $dc->getCurrentRecord();

        if (!$user || (int) $user->id !== (int) $dc->id || !($record[OperationVisibilityListener::FIELD_OWN] ?? false)) {
            return;
        }

        // Only a selection that was never saved is prefilled: the selection
        // fields are NULL until the user saves them (see keepEmptySelection()).
        if (null !== ($record[OperationVisibilityListener::FIELD_SHOW] ?? null) || null !== ($record[OperationVisibilityListener::FIELD_MENU] ?? null)) {
            return;
        }

        $config = $this->framework->getAdapter(Config::class);
        $data = [];

        foreach (self::SELECTION_FIELDS as $field) {
            $data[$field] = OperationVisibilityListener::FIELD_ALL === $field ? (int) (bool) $config->get($field) : (string) $config->get($field);
        }

        $this->connection->update('tl_user', $data, ['id' => (int) $dc->id]);

        // The form is rendered again from the cached record.
        DataContainer::clearCurrentRecordCache((int) $dc->id, 'tl_user');
    }

    /**
     * Contao saves an empty selection as NULL; store an empty list instead, so
     * a deliberately empty selection is not mistaken for one never saved.
     */
    #[AsCallback('tl_user', 'fields.iconVisibilityShow.save')]
    #[AsCallback('tl_user', 'fields.iconVisibilityMenu.save')]
    public function keepEmptySelection(mixed $value): mixed
    {
        return $value ?: serialize([]);
    }

    private function allowedUser(): BackendUser|null
    {
        $user = $this->security->getUser();

        return $user instanceof BackendUser && $this->visibility->allowsOwnSelection($user) ? $user : null;
    }
}
