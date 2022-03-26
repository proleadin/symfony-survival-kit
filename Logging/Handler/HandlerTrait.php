<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Handler;

use Leadin\SurvivalKitBundle\Logging\LogContext;
use Leadin\SurvivalKitBundle\Logging\Logger;

use Monolog\Logger as MonologLogger;
use Monolog\Utils;
use Monolog\Handler\StreamHandler as MonologStreamHandler;

trait HandlerTrait
{
    private string $sConfigPath;
    private ?array $aConfig = null;

    private function handleLog(array $aRecord): bool
    {
        if (parent::isHandling($aRecord)) {
            return parent::handle($aRecord);
        }

        if (MonologLogger::DEBUG !== $aRecord['level'] || !isset($aRecord['context']['context'])) {
            return false;
        }

        $this->loadConfig();

        $sContext = $aRecord['context']['context'];
        if (!isset($this->aConfig[$sContext]) || \time() > $this->aConfig[$sContext]) {
            return false;
        }

        return parent::handle($aRecord);
    }

    private function loadConfig()
    {
        // exit if config was loaded already
        if (!is_null($this->aConfig)) {
            return;
        }

        $sHandlerName = (new \ReflectionClass($this))->getShortName();
        try {
            $sConfigJson = \file_get_contents($this->sConfigPath);
        } catch (\Throwable $e) {
            // there is no config file by default, it's created when some debug logs have been activated
            // we do not want to raise an error when config file does not exist
            $this->aConfig = [];

            return;
        }

        $aConfig = \json_decode($sConfigJson, true);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            Logger::error("[$sHandlerName] Failed to decode debug manager config file: " . \json_last_error_msg(), LogContext::SSK_BUNDLE(), ['config' => $sConfigJson]);
            $aConfig = [];
        } else if (!\is_array($aConfig)) {
            Logger::error("[$sHandlerName] Debug manager config not valid", LogContext::SSK_BUNDLE(), ['config' => $sConfigJson]);
            $aConfig = [];
        }

        $this->aConfig = $aConfig;
    }
}
