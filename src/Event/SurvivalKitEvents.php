<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Event;

final class SurvivalKitEvents
{
    /**
     * Called when github webhook is processed.
     *
     * @Event("Leadin\SurvivalKitBundle\Event\DeploymentEvent")
     */
    public const DEPLOYMENT = 'survival_kit.deployment';
}
