<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Deployment\Github;

use Leadin\SurvivalKitBundle\Deployment\DeploymentCommand;

interface IGithubDeploymentService
{
    /**
     * Executes deployment commands
     */
    public function deploy(PullRequest $pullRequest): void;

    /**
     * Set service containing deployment commands
     */
    public function setDeploymentCommand(DeploymentCommand $deploymentCommand): self;
}
