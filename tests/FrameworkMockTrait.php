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
}
