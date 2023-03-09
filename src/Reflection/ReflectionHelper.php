<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Reflection;

class ReflectionHelper
{
    /**
     * Returns class name without namespace
     *
     * @param object|string $object
     */
    public static function getClassShortName($object): string
    {
        return (new \ReflectionClass($object))->getShortName();
    }

    /**
     * Returns class namespace name
     *
     * @param object|string $object
     */
    public static function getNamespace($object): string
    {
        return (new \ReflectionClass($object))->getNamespaceName();
    }
}
