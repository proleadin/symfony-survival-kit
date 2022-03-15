<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle;

use Leadin\SurvivalKitBundle\DependencyInjection\Facade;
use Leadin\SurvivalKitBundle\DependencyInjection\Compiler\AddFacadePass;
use Leadin\SurvivalKitBundle\DependencyInjection\Compiler\LoggerChannelPass;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SurvivalKitBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AddFacadePass());
        $container->addCompilerPass(new LoggerChannelPass());
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->container->has(Facade::CONTAINER)) {
            Facade::setFacadesContainer($this->container->get(Facade::CONTAINER));
        }
    }
}
