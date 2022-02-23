<?php

namespace Leadin\SurvivalKitBundle\Controller;

use Leadin\SurvivalKitBundle\Event\DeploymentEvent;
use Leadin\SurvivalKitBundle\Event\SurvivalKitEvents;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeploymentController extends AbstractController
{
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("deployment", name="survival_kit_deployment", methods={"GET"})
     */
    public function pullRequest(Request $request): JsonResponse
    {
        $data = ["message" => "Test endpoint"];
        $event = new DeploymentEvent($data);
        $this->eventDispatcher->dispatch($event, SurvivalKitEvents::DEPLOYMENT);

        return $this->json($event->getAData(), Response::HTTP_OK);
    }
}
