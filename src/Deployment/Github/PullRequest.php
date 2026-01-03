<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Deployment\Github;

/**
 * Contains github's pull_request event data
 */
class PullRequest
{
    protected const CLOSED_ACTION = 'closed';
    protected const COMPOSER_INSTALL_LABEL = 'composer-install';
    protected const DOCTRINE_MIGRATION_LABEL = 'doctrine-migration';
    protected const MERGE_ONLY_LABEL = 'merge-only';
    protected const ASSETS_COMPILE_LABEL = 'assets-compile';
    protected const ASSETS_INSTALL_LABEL = 'assets-install';
    protected const DEFAULT_BRANCH = 'master';

    protected array $aData;
    protected string $sAction;
    protected bool $bMerged;
    protected string $sTargetBranch;
    protected array $aLabels = [];

    public function __construct(string $sAction, array $aData)
    {
        $this->aData = $aData;
        $this->sAction = $sAction;
        $this->bMerged = $aData['merged'] ?? false;
        $this->sTargetBranch = $aData['base']['ref'] ?? '';
        if (isset($aData['labels']) && \is_array($aData['labels'])) {
            \array_map(fn(array $aLabel) => $this->aLabels[] = $aLabel['name'] ?? null, $aData['labels']);
        }
    }

    public function getData(): array
    {
        return $this->aData;
    }

    public function getAction(): string
    {
        return $this->sAction;
    }

    public function isClosed(): bool
    {
        return $this->sAction === self::CLOSED_ACTION;
    }

    public function isMergedIntoDefaultBranch(): bool
    {
        return $this->isClosed() && $this->bMerged && $this->sTargetBranch === self::DEFAULT_BRANCH;
    }

    public function hasDoctrineMigrationLabel(): bool
    {
        return $this->findLabel(self::DOCTRINE_MIGRATION_LABEL);
    }

    public function hasComposerInstallLabel(): bool
    {
        return $this->findLabel(self::COMPOSER_INSTALL_LABEL);
    }

    public function hasMergeOnlyLabel(): bool
    {
        return $this->findLabel(self::MERGE_ONLY_LABEL);
    }

    public function hasAssetsCompileLabel(): bool
    {
        return $this->findLabel(self::ASSETS_COMPILE_LABEL);
    }

    public function hasAssetsInstallLabel(): bool
    {
        return $this->findLabel(self::ASSETS_INSTALL_LABEL);
    }

    protected function findLabel(string $sLabel): bool
    {
        return \in_array($sLabel, $this->aLabels, true);
    }
}
