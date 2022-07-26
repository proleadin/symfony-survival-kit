<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class DebugManagerConfigStorage
{
    private const CONFIG_KEY = 'survival_kit.debug_manager_config';

    private CacheItemPoolInterface $configCachePool;

    public function __construct(CacheItemPoolInterface $configCachePool)
    {
        $this->configCachePool = $configCachePool;
    }

    public function getConfig(): array
    {
        $cacheItem = $this->configCachePool->getItem(self::CONFIG_KEY);
        if (!$cacheItem->isHit()) {
            // no config has ever been created
            return [];
        }

        return $this->decodeConfig($cacheItem);
    }

    public function updateConfig(string $sContext, string $sExpiration): void
    {
        $cacheItem = $this->configCachePool->getItem(self::CONFIG_KEY);
        if (!$cacheItem->isHit()) {
            $aConfig = [$sContext => $sExpiration];
        } else {
            $aConfig = $this->decodeConfig($cacheItem);
            $aConfig[$sContext] = $sExpiration;
        }

        $cacheItem->set(\json_encode($aConfig));
        $this->configCachePool->save($cacheItem);
    }

    private function decodeConfig(CacheItemInterface $cacheItem): array
    {
        $sConfigJson = $cacheItem->get();
        $aConfig = \json_decode($sConfigJson, true);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new \ValueError("Failed to decode debug manager config: " . \json_last_error_msg() . " | config: $sConfigJson");
        } else if (!\is_array($aConfig)) {
            throw new \ValueError("Debug manager config not valid: $sConfigJson");
        }

        return $aConfig;
    }
}