<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Handler;

use Leadin\SurvivalKitBundle\Logging\DebugManagerConfigStorage;
use Monolog\Handler\StreamHandler as MonologStreamHandler;
use Monolog\LogRecord;

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
    public function handle(LogRecord $record): bool
    {
        return $this->handleLog($record);
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(LogRecord $record): bool
    {
        return true;
    }
}
