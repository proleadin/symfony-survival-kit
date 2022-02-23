<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class DeploymentEvent extends Event
{
    protected array $aData;

    public function __construct(array $aData)
    {
        $this->aData = $aData;
    }

    public function getAData(): array
    {
        return $this->aData;
    }

    public function setAData($aData): self
    {
        $this->aData = $aData;

        return $this;
    }
}