<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Event;

final class SurvivalKitEvents
{
    /**
     * Called when received deployment request from github webhook.
     *
     * @Event("Leadin\SurvivalKitBundle\Event\GithubDeploymentEvent")
     */
    public const GITHUB_DEPLOYMENT = 'survival_kit.github_deployment';
}
