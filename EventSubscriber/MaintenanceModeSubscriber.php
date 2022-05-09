<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\EventSubscriber;

use Leadin\SurvivalKitBundle\Logging\LogContext;
use Leadin\SurvivalKitBundle\Logging\Logger;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/*
 * Checks if maintenance mode is enabled and prevents access to the site if needed
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    private const BUNDLE_ASSETS_URI = 'survivalkit/assets';
    private const BUNDLE_OPCACHE_RESET_URI = 'internal-tools/opcache-reset';

    private bool $bMaintenanceMode = false;
    private Environment $twig;

    public function __construct(bool $bMaintenanceMode, Environment $twig)
    {
        $this->bMaintenanceMode = $bMaintenanceMode;
        $this->twig = $twig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256]
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        Logger::debug("[MaintenaceModeSubscriber] Checking is maintenance mode enabled", LogContext::SSK_BUNDLE());

        if (!$this->bMaintenanceMode) {
            Logger::debug("[MaintenaceModeSubscriber] Maintenance mode disabled", LogContext::SSK_BUNDLE());

            return;
        } else if (!$event->isMasterRequest() || $this->isExcludedRequest($event)) {
            Logger::debug(
                "[MaintenaceModeSubscriber] Excluded request from maintenance mode : {$event->getRequest()->getRequestUri()}",
                LogContext::SSK_BUNDLE()
            );

            return;
        }

        $sQueryMaintenanceMode = $event->getRequest()->query->get('maintenance_mode');
        if (!is_null($sQueryMaintenanceMode) && !(bool)$sQueryMaintenanceMode) {
            Logger::debug("[MaintenaceModeSubscriber] Maintenance mode disabled by query param", LogContext::SSK_BUNDLE());

            return;
        }

        Logger::warning("[MaintenaceModeSubscriber] Maintenance mode enabled. Accessing site not possible!", LogContext::SSK_BUNDLE());

        // TODO documentation

        $sContent = $this->twig->render('@SurvivalKit/maintenance_mode.twig');
        $event->setResponse(new Response($sContent, Response::HTTP_SERVICE_UNAVAILABLE));
        $event->stopPropagation();
    }

    private function isExcludedRequest(RequestEvent $event): bool
    {
        $sRequestUri = $event->getRequest()->getRequestUri();

        return \strpos($sRequestUri, self::BUNDLE_ASSETS_URI) !== false
            || \strpos($sRequestUri, self::BUNDLE_OPCACHE_RESET_URI) !== false;
    }
}
