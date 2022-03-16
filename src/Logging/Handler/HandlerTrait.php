<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Handler;

use Monolog\Logger;
use Monolog\Utils;
use Monolog\Handler\StreamHandler as MonologStreamHandler;

trait HandlerTrait
{
    private string $sConfigPath;
    private ?array $aConfig = null;

    private function handleLog(array $aRecord): bool
    {
        $bIsHandlingByDefault = parent::isHandling($aRecord);
        if ($bIsHandlingByDefault) {
            return parent::handle($aRecord);
        }

        if ($aRecord['level'] !== Logger::DEBUG || !isset($aRecord['context']['context'])) {
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
        if (!is_null($this->aConfig)) {
            return;
        }

        $sConfigJson = @\file_get_contents($this->sConfigPath) ? : "";
        $this->aConfig = \json_decode($sConfigJson, true) ? : [];
    }
}
