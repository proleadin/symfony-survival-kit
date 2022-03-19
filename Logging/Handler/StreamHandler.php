<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Handler;

use Monolog\Logger;
use Monolog\Utils;
use Monolog\Handler\StreamHandler as MonologStreamHandler;

class StreamHandler extends MonologStreamHandler
{
    use HandlerTrait;

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
        return $this->handleLog($aRecord);
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $aRecord): bool
    {
        return true;
    }
}
