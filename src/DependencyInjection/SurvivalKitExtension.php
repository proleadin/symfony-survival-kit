<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\DependencyInjection;

use Leadin\SurvivalKitBundle\DependencyInjection\Compiler\LoggerChannelPass;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SurvivalKitExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $aConfigs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $aConfig = $this->processConfiguration($configuration, $aConfigs);

        if (isset($aConfig['deployment']['git_remote'])) {
             $container->setParameter('survival_kit.deployment.git_remote', $aConfig['deployment']['git_remote']);
        }
        if (isset($aConfig['deployment']['git_base_branch'])) {
             $container->setParameter('survival_kit.deployment.git_base_branch', $aConfig['deployment']['git_base_branch']);
        }
        if (isset($aConfig['deployment']['secret_token'])) {
             $container->setParameter('survival_kit.deployment.secret_token', $aConfig['deployment']['secret_token']);
        }

        $aHandlersToChannels = [];
        if (isset($aConfig['monolog']['handlers'])) {
            foreach ($aConfig['monolog']['handlers'] as $sName => $aHandler) {
                $aHandlers[] = [
                    'id' => $this->buildMonologHandler($container, $sName, $aHandler),
                    'channels' => empty($aHandler['channels']) ? null : $aHandler['channels']
                ];
            }

            foreach ($aHandlers as $aHandler) {
                $aHandlersToChannels[$aHandler['id']] = $aHandler['channels'];
            }

        }
        $container->setParameter(LoggerChannelPass::HANDLERS_TO_CHANNELS_PARAM, $aHandlersToChannels);

        $container->registerForAutoconfiguration(Facade::class)->addTag(Facade::TAG);
    }

    private function buildMonologHandler(ContainerBuilder $container, string $sName, array $aHandler): string
    {
        $sHandlerId = "monolog.handler.$sName";
        switch ($aHandler['type']) {
            case 'stream':
                $sHandlerClass = 'Leadin\SurvivalKitBundle\Logging\Handler\StreamHandler';
                $definition = new Definition($sHandlerClass);
                $definition->setArguments([
                    $aHandler['path'],
                    $aHandler['level'],
                    $aHandler['config']
                ]);
                break;

            case 'gelf':
                $sHandlerClass = 'Leadin\SurvivalKitBundle\Logging\Handler\GelfHandler';
                $definition = new Definition($sHandlerClass);
                $definition->setArguments([
                    // TODO
                ]);
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Invalid handler type "%s" given for handler "%s"', $aHandler['type'], $sName));
        }

        if (!empty($aHandler['formatter'])) {
            $definition->addMethodCall('setFormatter', [new Reference($aHandler['formatter'])]);
        }

        $container->setDefinition($sHandlerId, $definition);

        return $sHandlerId;
    }
}
