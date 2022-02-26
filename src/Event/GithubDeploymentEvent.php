<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Event;

use Leadin\SurvivalKitBundle\Deployment\Github\IGithubDeploymentService;
use Leadin\SurvivalKitBundle\Deployment\Github\PullRequest;

use Symfony\Contracts\EventDispatcher\Event;

final class GithubDeploymentEvent extends Event
{
    private PullRequest $pullRequest;
    private IGithubDeploymentService $deploymentService;

    public function __construct(PullRequest $pullRequest, IGithubDeploymentService $deploymentService)
    {
        $this->pullRequest = $pullRequest;
        $this->deploymentService = $deploymentService;
    }

    public function getPullRequest(): PullRequest
    {
        return $this->pullRequest;
    }

    public function setPullRequest(PullRequest $pullRequest): self
    {
        $this->pullRequest = $pullRequest;

        return $this;
    }

    public function getDeploymentService(): IGithubDeploymentService
    {
        return $this->deploymentService;
    }

    public function setDeploymentService(IGithubDeploymentService $deploymentService): self
    {
        $this->deploymentService = $deploymentService;

        return $this;
    }
}
