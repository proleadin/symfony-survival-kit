<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Deployment;

use Leadin\SurvivalKitBundle\Logging\LogContext;
use Leadin\SurvivalKitBundle\Logging\Logger;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Provides base shell commands uses for the deployment
 */
class DeploymentCommand
{
    protected ParameterBagInterface $parameterBag;
    protected string $sProjectDir;
    protected string $sAppEnv;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
        $this->sProjectDir = $parameterBag->get('kernel.project_dir');
        $this->sAppEnv = $parameterBag->get('kernel.environment');
    }

    /**
     * @throws ProcessFailedException
     */
    public function gitPull(): void
    {
        $sGitRemote = $this->parameterBag->get('survival_kit.deployment.git_remote');
        $sGitBaseBranch = $this->parameterBag->get('survival_kit.deployment.git_base_branch');

        $process = Process::fromShellCommandline("git pull $sGitRemote $sGitBaseBranch", "/");
        $this->execute($process, "Git pull $sGitRemote $sGitBaseBranch");
    }

    /**
     * @throws ProcessFailedException
     */
    public function composerInstall(): void
    {
        $process = Process::fromShellCommandline("composer install", $this->sProjectDir);
        $this->execute($process, "Composer install");
    }

    /**
     * @throws ProcessFailedException
     */
    public function symfonyClearCache(): void
    {
        $process = Process::fromShellCommandline("bin/console cache:clear -e {$this->sAppEnv}", $this->sProjectDir);
        $this->execute($process, "Symfony Clear Cache");
    }

    /**
     * @throws ProcessFailedException
     */
    public function composerDumpAutoload(): void
    {
        $process = Process::fromShellCommandline("composer dump-autoload --classmap-authoritative", $this->sProjectDir);
        $this->execute($process, "Composer dump-autoload");
    }

    /**
     * Creates the .env.local.php file with the environment variables
     * Symfony will not spend time parsing the .env files
     *
     * @throws ProcessFailedException
     */
    public function composerDumpEnv(): void
    {
        $process = Process::fromShellCommandline("composer dump-env {$this->sAppEnv}", $this->sProjectDir);
        $this->execute($process, "Composer dump-env");
    }

    /**
     * @throws ProcessFailedException
     */
    public function opcacheReset(): void
    {
        \opcache_reset();
        Logger::notice("OPcache reset succeeded", LogContext::DEPLOYMENT());
    }

    /**
     * @throws ProcessFailedException
     */
    public function doctrineMigrationsMigrate(): void
    {
        $process = Process::fromShellCommandline("bin/console doctrine:migrations:migrate -n", $this->sProjectDir);
        $this->execute($process, "Doctrine Migrations Migrate");
    }

    /**
     * For the Symfony Messenger component
     * This will signal to each worker that it should finish the message it's currently handling and shut down gracefully.
     *
     * @throws ProcessFailedException
     */
    public function restartMessengerWorkers(): void
    {
        $process = Process::fromShellCommandline("bin/console messenger:stop-workers", $this->sProjectDir);
        $this->execute($process, "Restart messenger workers");
    }

    /**
     * @throws ProcessFailedException
     */
    protected function execute(Process $process, string $sCommandName): void
    {
        Logger::notice("Running $sCommandName", LogContext::DEPLOYMENT());
        $process->run();
        if (!$process->isSuccessful()) {
            $e = new ProcessFailedException($process);
            Logger::exception("$sCommandName failed", LogContext::DEPLOYMENT(), $e);
            throw $e;
        }
        Logger::notice("$sCommandName succeeded", LogContext::DEPLOYMENT(), ['output' => $process->getOutput()]);
    }
}
