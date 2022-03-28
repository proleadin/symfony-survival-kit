<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\DependencyInjection;

use Psr\Container\ContainerInterface;

abstract class Facade
{
    public const TAG        = 'survival_kit.facade';
    public const CONTAINER  = 'survival_kit.facade.container';

    protected static ?ContainerInterface $facadesContainer = null;

    public static function setFacadesContainer(ContainerInterface $facadesContainer): void
    {
        static::$facadesContainer = $facadesContainer;
    }

    /**
     * Returns Id of the Facade service
     */
    abstract protected static function getServiceId(): string;

    /**
     * Trigerred when static method called on the Facade object
     * e.g. Logger::error()
     */
    public static function __callStatic(string $sMethod, array $aArguments)
    {
        $sClass = static::class;
        if (!static::$facadesContainer || static::$facadesContainer->has($sClass) === false) {
            throw new \RuntimeException(sprintf("$sClass not registered"));
        }

        $service = static::$facadesContainer->get($sClass);
        return $service->$sMethod(...$aArguments);
    }
}