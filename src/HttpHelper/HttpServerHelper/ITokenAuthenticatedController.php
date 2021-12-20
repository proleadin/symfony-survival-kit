<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\HttpHelper\HttpServerHelper;

/**
 * Requests to routes in Controllers implementing this interface will be automatically checked for the
 * authentication header validity
 * @see EventSubscriber/AuthenticationHeaderSubscriber.php
 */
interface ITokenAuthenticatedController
{
    /**
     * Returns authentication token
     */
    public function getToken(): string;
}
