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

        try {
            $data = \json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $request->request->replace(\is_array($data) ? $data : []);
        } catch (\JsonException $e) {
            $sError = $e->getMessage();
            Logger::info("Invalid json body: $sError", LogContext::SSK_BUNDLE(), [
                "requestContent" => $request->getContent()
            ]);

            $response = new JsonResponse([
                "s_message" => "Invalid request payload",
                "error" => ["json decoding error" => [$sError]]
            ], JsonResponse::HTTP_BAD_REQUEST);

            $response->send();
        }
    }
}
