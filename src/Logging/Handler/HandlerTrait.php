<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Handler;

use Leadin\SurvivalKitBundle\Logging\DebugManagerConfigStorage;
use Leadin\SurvivalKitBundle\Logging\LogContext;
use Leadin\SurvivalKitBundle\Logging\Logger;

use Monolog\Logger as MonologLogger;
use Monolog\Utils;
use Monolog\Handler\StreamHandler as MonologStreamHandler;

trait HandlerTrait
{
    private ?array $aConfig = null;
    private ?DebugManagerConfigStorage $debugManagerConfigStorage = null;

    /**
     * Checks if debug logs activated by debug manager
     */
    private function handleLog(array $aRecord): bool
    {
        if (parent::isHandling($aRecord)) {
            return parent::handle($aRecord);
        }

        if (MonologLogger::DEBUG !== $aRecord['level'] || !isset($aRecord['context']['context'])) {
            return false;
        }

        $this->loadDebugManagerConfig();

        $sContext = $aRecord['context']['context'];
        if (!isset($this->aConfig[$sContext]) || \time() > $this->aConfig[$sContext]) {
            return false;
        }

        return parent::handle($aRecord);
    }

    private function loadDebugManagerConfig()
    {
        // exit if config has been loaded already
        if (!is_null($this->aConfig)) {
            return;
        }

        if (!$this->debugManagerConfigStorage) {
            throw new \InvalidArgumentException("DebugManagerConfigStorage service not set");
        }

        try {
            $this->aConfig = $this->debugManagerConfigStorage->getConfig();
        } catch (\Throwable $e) {
            // we do not want to throw an error when failed to load config
            // so as do not break an app
            $sHandlerName = (new \ReflectionClass($this))->getShortName();
            Logger::exception("[$sHandlerName] Loading debug manager config failed", LogContext::SSK_BUNDLE(), $e);

            $this->aConfig = [];
        }
    }
}
