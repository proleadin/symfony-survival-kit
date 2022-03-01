<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Deployment\Github;

/**
 * Contains github's pull_request event data
 */
class PullRequest
{
    protected const CLOSED_ACTION = 'closed';
    protected const COMPOSER_INSTALL_LABEL = 'composer-install';
    protected const DOCTRINE_MIGRATION_LABEL = 'doctrine-migration';

    protected array $aData;
    protected string $sAction;
    protected bool $bMerged;
    protected array $aLabels = [];

    public function __construct(string $sAction, array $aData)
    {
        $this->aData = $aData;
        $this->sAction = $sAction;
        $this->bMerged = $aData['merged'] ?? false;
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

    public function isPullRequestClosed(): bool
    {
        return $this->sAction === self::CLOSED_ACTION;
    }

    public function isPullRequestMerged(): bool
    {
        return $this->bMerged;
    }

    public function hasDoctrineMigrationLabel(): bool
    {
        return $this->findLabel(self::DOCTRINE_MIGRATION_LABEL);
    }

    public function hasComposerInstallLabel(): bool
    {
        return $this->findLabel(self::COMPOSER_INSTALL_LABEL);
    }

    protected function findLabel(string $sLabel): bool
    {
        return \in_array($sLabel, $this->aLabels, true);
    }
}
