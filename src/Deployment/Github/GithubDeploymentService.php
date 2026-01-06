<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Deployment\Github;

use Leadin\SurvivalKitBundle\Logging\LogContext;
use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\Deployment\DeploymentCommand;

/**
 * Executes deployment commands for the request coming from Github webhook
 */
class GithubDeploymentService implements IGithubDeploymentService
{
    protected DeploymentCommand $deploymentCommand;

    /**
     * @required
     *
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
        if ($pullRequest->hasMergeOnlyLabel()) {
            Logger::notice("Deployment not proceed, 'merge-only' label found", LogContext::DEPLOYMENT());
        } else {
            $this->executeDeploymentCommands($pullRequest);
        }
    }

    /**
     * Override this method if custom deployment commands needed
     */
    protected function executeDeploymentCommands(PullRequest $pullRequest): void
    {
        $this->deploymentCommand->gitPull();

        $pullRequest->hasComposerInstallLabel() && $this->deploymentCommand->composerInstall();
        $this->deploymentCommand->composerDumpAutoload();
        $pullRequest->hasDoctrineMigrationLabel() && $this->deploymentCommand->doctrineMigrationsMigrate();
        $pullRequest->hasAssetsInstallLabel() && $this->deploymentCommand->installAssets();
        $pullRequest->hasAssetsCompileLabel() && $this->deploymentCommand->compileAssets();

        $this->deploymentCommand->symfonyClearCache();
        $this->deploymentCommand->composerDumpEnv();
        $this->deploymentCommand->opcacheReset();
    }
}
