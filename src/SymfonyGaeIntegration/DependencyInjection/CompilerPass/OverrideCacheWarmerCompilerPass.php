<?php

namespace Jhg\SymfonyGaeIntegration\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;


/**
 * Class OverrideCacheWarmerCompilerPass
 */
class OverrideCacheWarmerCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('cache_warmer');
        $definition->setClass('Jhg\SymfonyGaeIntegration\HttpKernel\CacheWarmer\CacheWarmerAggregate');
        $definition->addArgument($container->getParameter('kernel.compiled_dir'));
        $definition->addArgument($container->getParameter('kernel.debug'));
    }
}