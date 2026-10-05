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

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Mandrael\ContaoBackendIconVisibilityBundle\EventListener\OperationVisibilityListener;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

trait FrameworkMockTrait
{
    /**
     * One adapter for Config, Controller and System: "get" reads $config, all
     * other static calls (loadDataContainer, loadLanguageFile) do nothing.
     *
     * @param array<string, mixed> $config
     */
    private function mockFramework(array $config = []): ContaoFramework
    {
        $adapter = $this->createStub(Adapter::class);

        $adapter
            ->method('__call')
            ->willReturnCallback(static fn (string $method, array $args): mixed => 'get' === $method ? $config[$args[0]] ?? null : null)
        ;

        $framework = $this->createStub(ContaoFramework::class);
        $framework->method('getAdapter')->willReturn($adapter);

        return $framework;
    }

    /**
     * @param list<string> $grantedModules
     */
    private function mockSecurity(UserInterface|null $user = null, array $grantedModules = []): Security
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturnCallback(static fn (mixed $attribute, mixed $module = null): bool => \in_array($module, $grantedModules, true));

        return $security;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function visibility(array $config = [], UserInterface|null $user = null, Connection|null $connection = null): OperationVisibilityListener
    {
        return new OperationVisibilityListener($this->mockFramework($config), $this->mockSecurity($user), $connection ?? $this->createStub(Connection::class));
    }
}
