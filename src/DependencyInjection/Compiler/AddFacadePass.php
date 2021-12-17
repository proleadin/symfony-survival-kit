<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\DependencyInjection\Compiler;

use Leadin\SurvivalKitBundle\DependencyInjection\Facade;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddFacadePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $aFacades = [];
        if ($container->has(Facade::class)) {
            $aTaggedServices = $container->findTaggedServiceIds(Facade::TAG, $container);
            foreach ($aTaggedServices as $sServiceId => $aTags) {
                $sClass = $container->getDefinition($sServiceId)->getClass() ? : $sServiceId;
                $reflectionFacade = new \ReflectionMethod($sClass, 'getServiceId');
                $reflectionFacade->setAccessible(true);
                $sRealizationClass = $reflectionFacade->invoke(null);
                $aFacades[$sServiceId] = new Reference($sRealizationClass);
            }
        }
        $container->setAlias(Facade::CONTAINER, new Alias((string)ServiceLocatorTagPass::register($container, $aFacades), true));
    }
}
