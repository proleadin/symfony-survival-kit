<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Handler;

use Leadin\SurvivalKitBundle\Logging\DebugManagerConfigStorage;

use Monolog\Logger;
use Monolog\Utils;
use Monolog\Handler\StreamHandler as MonologStreamHandler;

class StreamHandler extends MonologStreamHandler
{
    use HandlerTrait;

    public function __construct(string $sStream, string $sLevel, DebugManagerConfigStorage $debugManagerConfigStorage)
    {
        parent::__construct($sStream, $sLevel);

        $this->debugManagerConfigStorage = $debugManagerConfigStorage;
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
