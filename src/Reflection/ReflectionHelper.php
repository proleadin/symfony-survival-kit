<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Reflection;

class ReflectionHelper
{
    /**
     * Returns class name without namespace
     *
     * @param object|string $object
     * @return string
     */
    public static function getClassShortName($object): string
    {
        return (new \ReflectionClass($object))->getShortName();
    }

    public static function getNamespace($object): string
    {
        return (new \ReflectionClass($object))->getNamespaceName();
    }
}
