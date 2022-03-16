<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Handler;

use Gelf\PublisherInterface;
use Monolog\Handler\GelfHandler as MonologGelfHandler;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\GelfMessageFormatter;

class GelfHandler extends MonologGelfHandler
{
    use HandlerTrait;

    private ?string $sAppName;

    public function __construct(PublisherInterface $publisher, string $sLevel, string $sConfigPath, string $sAppName)
    {
        parent::__construct($publisher, $sLevel);

        $this->sConfigPath = $sConfigPath;
        $this->sAppName = $sAppName;
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

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new GelfMessageFormatter($this->sAppName);
    }
}
