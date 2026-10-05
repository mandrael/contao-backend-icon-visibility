<?php

declare(strict_types=1);

/*
 * This file is part of mandrael/contao-backend-icon-visibility.
 *
 * (c) Michael Gasperl
 *
 * @license MIT
 */

namespace Mandrael\ContaoBackendIconVisibilityBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Mandrael\ContaoBackendIconVisibilityBundle\DataContainer\OperationsBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Swaps in the builder with distinct "new after/into" icons, but only if the
 * service still uses Contao's class and addNewButton() has the known signature.
 */
class OperationsBuilderPass implements CompilerPassInterface
{
    public const SERVICE = 'contao.data_container.operations_builder';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::SERVICE) || !self::isCompatible()) {
            return;
        }

        $definition = $container->getDefinition(self::SERVICE);

        if (DataContainerOperationsBuilder::class === $definition->getClass()) {
            $definition->setClass(OperationsBuilder::class);
        }
    }

    public static function isCompatible(): bool
    {
        $class = new \ReflectionClass(DataContainerOperationsBuilder::class);

        if ($class->isFinal() || !$class->hasMethod('addNewButton')) {
            return false;
        }

        $method = $class->getMethod('addNewButton');

        if ($method->isFinal()) {
            return false;
        }

        $signature = array_map(
            static fn (\ReflectionParameter $parameter): string => $parameter->getName().':'.$parameter->getType(),
            $method->getParameters(),
        );

        return ['mode:string', 'table:string', 'pid:int', 'id:?int'] === $signature && \in_array((string) $method->getReturnType(), ['self', DataContainerOperationsBuilder::class], true);
    }
}
