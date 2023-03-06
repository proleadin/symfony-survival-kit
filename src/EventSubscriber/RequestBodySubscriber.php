<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\EventSubscriber;

use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\Logging\LogContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
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
            $sError = \json_last_error_msg();
            Logger::info("Invalid json body: $sError", LogContext::SSK_BUNDLE(), [
                "requestContent" => $request->getContent()
            ]);

            $response = new JsonResponse([
                "message" => "Invalid request payload",
                "error" => $sError
            ], JsonResponse::HTTP_BAD_REQUEST);

            $response->send();
        } else {
            $request->request->replace(\is_array($data) ? $data : []);
        }
    }
}
