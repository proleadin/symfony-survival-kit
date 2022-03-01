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

    /**
     * {@inheritdoc}
     */
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

    /**
     * Override this method if custom deployment commands needed
     */
    protected function executeDeploymentCommands(PullRequest $pullRequest): void
    {
        $this->deploymentCommand->gitPull();

        $pullRequest->hasComposerInstallLabel() && $this->deploymentCommand->composerInstall();
        $pullRequest->hasDoctrineMigrationLabel() && $this->deploymentCommand->doctrineMigrationsMigrate();

        $this->deploymentCommand->symfonyClearCache();
        $this->deploymentCommand->opcacheReset();
        $this->deploymentCommand->composerDumpAutoload();
        $this->deploymentCommand->composerDumpEnv();
    }
}
