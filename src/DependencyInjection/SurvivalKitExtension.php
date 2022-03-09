<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SurvivalKitExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['deployment']['git_remote'])) {
             $container->setParameter('survival_kit.deployment.git_remote', $config['deployment']['git_remote']);
        }
        if (isset($config['deployment']['git_base_branch'])) {
             $container->setParameter('survival_kit.deployment.git_base_branch', $config['deployment']['git_base_branch']);
        }
        if (isset($config['deployment']['secret_token'])) {
             $container->setParameter('survival_kit.deployment.secret_token', $config['deployment']['secret_token']);
        }

        $container->registerForAutoconfiguration(Facade::class)->addTag(Facade::TAG);
    }
}
