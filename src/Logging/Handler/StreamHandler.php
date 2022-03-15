<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Handler;

use Monolog\Logger;
use Monolog\Utils;
use Monolog\Handler\StreamHandler as MonologStreamHandler;

class StreamHandler extends MonologStreamHandler
{
    private string $sConfigPath;
    private ?array $aConfig = null;

    public function __construct(string $sStream, string $sLevel, string $sConfigPath)
    {
        parent::__construct($sStream, $sLevel);

        $this->sConfigPath = $sConfigPath;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $aRecord): bool
    {
file_put_contents('/code/pbernad/pbernad.log', PHP_EOL . 'handle_' . $aRecord['message'] . PHP_EOL, FILE_APPEND);

        $bIsHandlingByDefault = parent::isHandling($aRecord);
        if ($bIsHandlingByDefault) {
            return parent::handle($aRecord);
        }

        if ($aRecord['level'] !== Logger::DEBUG) {
file_put_contents('/code/pbernad/pbernad.log', PHP_EOL . '24' . PHP_EOL, FILE_APPEND);
            return false;
        } else if (!isset($aRecord['context']['context'])) {
file_put_contents('/code/pbernad/pbernad.log', PHP_EOL . '27' . PHP_EOL, FILE_APPEND);
            return false;
        }

        $this->loadConfig();

        $sContext = $aRecord['context']['context'];
        if (!isset($this->aConfig[$sContext])) {
file_put_contents('/code/pbernad/pbernad.log', PHP_EOL . '35' . PHP_EOL, FILE_APPEND);
            return false;
        } else if (\time() > $this->aConfig[$sContext]) {
file_put_contents('/code/pbernad/pbernad.log', PHP_EOL . '38' . PHP_EOL, FILE_APPEND);
            return false;
        }

        return parent::handle($aRecord);
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $aRecord): bool
    {
file_put_contents('/code/pbernad/pbernad.log', PHP_EOL . 'isHandling' . PHP_EOL, FILE_APPEND);

        return true;
    }

    private function loadConfig()
    {
file_put_contents('/code/pbernad/pbernad.log', PHP_EOL . 'loadConfig' . PHP_EOL, FILE_APPEND);

        if (!is_null($this->aConfig)) {
            return;
        }

        $sConfigJson = @\file_get_contents($this->sConfigPath) ? : "";
        $this->aConfig = \json_decode($sConfigJson, true) ? : [];

file_put_contents('/code/pbernad/pbernad.log', PHP_EOL . 'config_loaded' . PHP_EOL, FILE_APPEND);
    }
}
