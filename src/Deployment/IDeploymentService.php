<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Deployment;

interface IDeploymentService
{
    /**
     * Run deployment commands
     */
    public function deploy(): void;
}
