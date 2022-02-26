<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Deployment\Github;

use Leadin\SurvivalKitBundle\Deployment\DeploymentCommand;
use Leadin\SurvivalKitBundle\Deployment\GithubDeploymentException;

/**
 * Executes deployment commands for the request coming from Github webhook
 */
class GithubDeploymentService implements IGithubDeploymentService
{
    protected DeploymentCommand $deploymentCommand;

    public function setDeploymentCommand(DeploymentCommand $deploymentCommand): self
    {
        $this->deploymentCommand = $deploymentCommand;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deploy(PullRequest $pullRequest): void
    {
        if (!$pullRequest->isPullRequestClosed()) {
            throw new GithubDeploymentException("Pull request not closed");
        } else if (!$pullRequest->isPullRequestMerged()) {
            throw new GithubDeploymentException("Pull request not merged");
        }

        $this->executeDeploymentCommands($pullRequest);
    }

    protected function executeDeploymentCommands(PullRequest $pullRequest): void
    {
        // TODO get from config
        $this->deploymentCommand->gitPull('upstream', 'master');

        $pullRequest->hasComposerInstallLabel() && $this->deploymentCommand->composerInstall();

        $this->deploymentCommand->composerDumpAutoload();
        $this->deploymentCommand->symfonyClearCache();
        $this->deploymentCommand->composerDumpEnv();
    }
}
