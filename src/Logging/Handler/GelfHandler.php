<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Handler;

use Leadin\SurvivalKitBundle\Logging\DebugManagerConfigStorage;
use Gelf\PublisherInterface;
use Monolog\Handler\GelfHandler as MonologGelfHandler;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\GelfMessageFormatter;
use Monolog\LogRecord;

class GelfHandler extends MonologGelfHandler
{
    use HandlerTrait;

    private ?string $sAppName;

    public function __construct(
        PublisherInterface $publisher,
        string $sLevel,
        string $sAppName,
        DebugManagerConfigStorage $debugManagerConfigStorage
    ) {
        parent::__construct($publisher, $sLevel);

        $this->sAppName = $sAppName;
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

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new GelfMessageFormatter($this->sAppName);
    }
}
