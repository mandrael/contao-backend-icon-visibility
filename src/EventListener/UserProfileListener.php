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
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * The own selection in the user profile: hidden unless a user group allows it,
 * prefilled with the system settings when the user switches it on.
 */
class UserProfileListener
{
    public const LEGEND = '{icon_visibility_legend},'.OperationVisibilityListener::FIELD_OWN;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly OperationVisibilityListener $visibility,
        private readonly Security $security,
        private readonly Connection $connection,
    ) {
    }

    #[AsCallback('tl_user', 'config.onload')]
    public function hideIfNotAllowed(): void
    {
        $user = $this->security->getUser();

        if ($user instanceof BackendUser && $this->visibility->allowsOwnSelection($user)) {
            return;
        }

        // Contao copies the "login" palette to "default" on the profile page.
        foreach (['login', 'default'] as $palette) {
            if (isset($GLOBALS['TL_DCA']['tl_user']['palettes'][$palette])) {
                $GLOBALS['TL_DCA']['tl_user']['palettes'][$palette] = str_replace(';'.self::LEGEND, '', $GLOBALS['TL_DCA']['tl_user']['palettes'][$palette]);
            }
        }
    }

    #[AsCallback('tl_user', 'fields.iconVisibilityOwn.save')]
    public function prefill(mixed $value, DataContainer $dc): mixed
    {
        $record = $dc->getCurrentRecord() ?? [];

        if (!$value || ($record[OperationVisibilityListener::FIELD_OWN] ?? false)) {
            return $value;
        }

        $fields = [OperationVisibilityListener::FIELD_ALL, OperationVisibilityListener::FIELD_SHOW, OperationVisibilityListener::FIELD_MENU];

        // Only a selection that was never made is prefilled.
        foreach ($fields as $field) {
            if (array_filter(StringUtil::deserialize($record[$field] ?? null, true))) {
                return $value;
            }
        }

        $config = $this->framework->getAdapter(Config::class);
        $data = [];

        foreach ($fields as $field) {
            $data[$field] = OperationVisibilityListener::FIELD_ALL === $field ? (int) (bool) $config->get($field) : $config->get($field);
        }

        $this->connection->update('tl_user', $data, ['id' => (int) $dc->id]);

        return $value;
    }
}
