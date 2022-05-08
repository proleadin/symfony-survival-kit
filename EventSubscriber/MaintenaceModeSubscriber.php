<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\EventSubscriber;

use Leadin\SurvivalKitBundle\Logging\LogContext;
use Leadin\SurvivalKitBundle\Logging\Logger;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/*
 * Checks if maintenance mode is enabled and prevents access to the site if needed
 */
class MaintenaceModeSubscriber implements EventSubscriberInterface
{
    private bool $bMaintenanceMode = false;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->bMaintenanceMode = $parameterBag->get('survival_kit.maintenance_mode');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest() || !$this->bMaintenanceMode) {
            return;
        }

        // TODO check for query param

        // $request = $requestEvent->getRequest();
        $content = "MAINTENACE MODE";
        $event->setResponse(new Response($content, 503));
        $event->stopPropagation();
    }
}
