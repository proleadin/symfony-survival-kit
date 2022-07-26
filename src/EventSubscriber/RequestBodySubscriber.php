<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Responsible for parsing request json body and putting parsed data into the Request object
 */
class RequestBodySubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController'
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->getContentType() !== 'json' || !$request->getContent()) {
            return;
        }

        $data = \json_decode($request->getContent(), true);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('Invalid json body: ' . json_last_error_msg());
        }
        $request->request->replace(\is_array($data) ? $data : []);
    }
}
