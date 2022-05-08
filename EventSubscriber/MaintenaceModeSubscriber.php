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
class MaintenaceModeSubscriber implements EventSubscriberInterface
{
    private const BUNDLE_ASSETS_URI = 'survivalkit/assets';

    private bool $bMaintenanceMode = false;
    private Environment $twig;

    public function __construct(ParameterBagInterface $parameterBag, Environment $twig)
    {
        $this->bMaintenanceMode = $parameterBag->get('survival_kit.maintenance_mode');
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
        if (!$this->bMaintenanceMode || !$event->isMasterRequest() || $this->isBundleAssetsRequest($event)) {
            return;
        }

        $sQueryMaintenanceMode = $event->getRequest()->query->get('maintenance_mode');
        if (!is_null($sQueryMaintenanceMode) && !(bool)$sQueryMaintenanceMode) {
            return;
        }

        $sContent = $this->twig->render('@SurvivalKit/maintenance_mode/works_in_progress.twig');
        $event->setResponse(new Response($sContent, Response::HTTP_SERVICE_UNAVAILABLE));
        $event->stopPropagation();
    }

    private function isBundleAssetsRequest(RequestEvent $event): bool
    {
        return \strpos($event->getRequest()->getRequestUri(), self::BUNDLE_ASSETS_URI) !== false;
    }
}
