<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wire monolog handlers to channels
 */
class LoggerChannelPass implements CompilerPassInterface
{
    public const HANDLERS_TO_CHANNELS_PARAM = 'survival_kit.monolog.handlers_to_channels';

    private array $aChannels = ['app'];

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('monolog.logger')) {
            return;
        }

        $aHandlersToChannels = $container->getParameter(self::HANDLERS_TO_CHANNELS_PARAM);
        foreach ($aHandlersToChannels as $sHandler => $aChannels) {
            foreach ($this->processChannels($aChannels) as $sChannel) {
                try {
                    $logger = $container->getDefinition($sChannel === 'app' ? 'monolog.logger' : 'monolog.logger.'.$sChannel);
                } catch (InvalidArgumentException $e) {
                    $msg = 'Monolog configuration error: The logging channel "'.$sChannel.'" assigned to the "'.substr($sHandler, 16).'" handler does not exist.';
                    throw new \InvalidArgumentException($msg, 0, $e);
                }
                $logger->addMethodCall('pushHandler', [new Reference($sHandler)]);
            }
        }
    }

    private function processChannels(?array $aConfiguration): array
    {
        if (null === $aConfiguration) {
            return $this->aChannels;
        }

        if ('inclusive' === $aConfiguration['type']) {
            return $aConfiguration['elements'] ?: $this->aChannels;
        }

        return array_diff($this->aChannels, $aConfiguration['elements']);
    }
}
