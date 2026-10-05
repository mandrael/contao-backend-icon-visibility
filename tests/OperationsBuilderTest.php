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

use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Mandrael\ContaoBackendIconVisibilityBundle\DataContainer\OperationsBuilder;
use Mandrael\ContaoBackendIconVisibilityBundle\DependencyInjection\Compiler\OperationsBuilderPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OperationsBuilderTest extends TestCase
{
    public function testTheInstalledContaoVersionIsCompatible(): void
    {
        // Fails on a Contao version whose builder changed; the bundle then
        // keeps Contao's icon, but the icons should be adapted.
        $this->assertTrue(OperationsBuilderPass::isCompatible());
    }

    public function testReplacesOnlyContaosOwnBuilder(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(OperationsBuilderPass::SERVICE, new Definition(DataContainerOperationsBuilder::class));
        $container->setDefinition('other', new Definition(DataContainerOperationsBuilder::class));

        (new OperationsBuilderPass())->process($container);

        $this->assertSame(OperationsBuilder::class, $container->getDefinition(OperationsBuilderPass::SERVICE)->getClass());
        $this->assertSame(DataContainerOperationsBuilder::class, $container->getDefinition('other')->getClass());

        $container->getDefinition(OperationsBuilderPass::SERVICE)->setClass('App\CustomBuilder');
        (new OperationsBuilderPass())->process($container);

        $this->assertSame('App\CustomBuilder', $container->getDefinition(OperationsBuilderPass::SERVICE)->getClass());
    }

    public function testTheIconsExist(): void
    {
        foreach (OperationsBuilder::ICONS as $path) {
            $this->assertFileExists(\dirname(__DIR__).'/public/'.substr($path, \strlen('bundles/mandraelcontaobackendiconvisibility/')));
        }
    }
}
