<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\EventSubscriber;

use Leadin\SurvivalKitBundle\HttpHelper\HttpServerHelper\ITokenAuthenticatedController;
use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\Logging\LogContext;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Authenticates incoming requests for the controllers implementing ITokenAuthenticatedController
 * 
 * Handles headers types:
 * - Authorization
 * - X-Hub-Signature
 * 
 * Handles query params:
 * - api_key
 */
class AuthenticationSubscriber implements EventSubscriberInterface
{
    private const BEARER_TOKEN_TYPE = 'Bearer';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 100]
        ];
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        // when a controller class defines multiple action methods, the controller
        // is returned as [$controllerInstance, 'methodName']
        if (\is_array($controller)) {
            $controller = $controller[0];
        }

        if (!$controller instanceof ITokenAuthenticatedController) {
            return;
        }

        $request = $event->getRequest();
        $sRouteName = $request->attributes->get('_route');
        Logger::debug("Authenticating access to route: $sRouteName", LogContext::SECURITY());

        $sRequestToken = '';
        $sAccessToken = $controller->getToken();

        if ($request->headers->get('Authorization')) {
            $sRequestToken = $request->headers->get('Authorization');
            if (\strpos($sRequestToken, self::BEARER_TOKEN_TYPE) !== false) {
                $sRequestToken = \substr($sRequestToken, \strlen(self::BEARER_TOKEN_TYPE) + 1);
            }
        } elseif ($request->headers->get('X-Hub-Signature')) {
            $sPayload = $request->getContent();
            $sHubSignature = $request->headers->get('X-Hub-Signature');
            list($sAlgorithm, $sHash) = explode('=', $sHubSignature, 2);
            if (!\hash_equals(\hash_hmac($sAlgorithm, $sPayload, $sAccessToken), $sHash)) {
                $this->denyAccess($sRouteName, $sHash);
            }
            return;
        } elseif ($sApiKey = $request->query->get('api_key')) {
            $sRequestToken = $sApiKey;
        }

        if ($sRequestToken !== $sAccessToken) {
            $this->denyAccess($sRouteName, $sRequestToken);
        }
    }

    private function denyAccess(string $sRouteName, string $sRequestToken) {
        Logger::warning("Access denied to route: $sRouteName", LogContext::SECURITY(), ['requestToken' => $sRequestToken]);
        throw new AccessDeniedHttpException('This request needs a valid token!');
    }
}
