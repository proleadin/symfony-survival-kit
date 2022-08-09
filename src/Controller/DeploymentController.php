<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Controller;

use Leadin\SurvivalKitBundle\Deployment\Exception\GithubDeploymentException;
use Leadin\SurvivalKitBundle\Deployment\Github\PullRequest;
use Leadin\SurvivalKitBundle\Deployment\Github\IGithubDeploymentService;
use Leadin\SurvivalKitBundle\Event\GithubDeploymentEvent;
use Leadin\SurvivalKitBundle\Event\SurvivalKitEvents;
use Leadin\SurvivalKitBundle\HttpHelper\HttpServerHelper\ITokenAuthenticatedController;
use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\Logging\LogContext;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/deployment", name="survival_kit_deployment_")
 */
class DeploymentController extends AbstractController implements ITokenAuthenticatedController
{
    private EventDispatcherInterface $eventDispatcher;
    private IGithubDeploymentService $githubDeploymentService;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        IGithubDeploymentService $githubDeploymentService
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->githubDeploymentService = $githubDeploymentService;
    }

    /**
     * @Route("/github-webhook", name="github_webhook", methods={"POST"})
     */
    public function githubWebhook(Request $request): JsonResponse
    {
        Logger::debug("[DeploymentController] Github webhook deployment request received", LogContext::DEPLOYMENT(), ['payload' => $request->getContent()]);

        $sAction = $request->request->get('action');
        $aPullRequestData = $request->request->get('pull_request');
        if (!$sAction) {
            Logger::error("[DeploymentController] Github webhook bad request : field 'action' must not be blank", LogContext::DEPLOYMENT());

            return $this->json(["message" => "field 'action' must not be blank"], Response::HTTP_BAD_REQUEST);
        } elseif (!$aPullRequestData || !is_array($aPullRequestData)) {
            Logger::error("[DeploymentController] Github webhook bad request : field 'pull_request' must not be blank", LogContext::DEPLOYMENT());

            return $this->json(["message" => "field 'pull_request' must not be blank"], Response::HTTP_BAD_REQUEST);
        }

        $pullRequest = new PullRequest($sAction, $aPullRequestData);
        $event = new GithubDeploymentEvent($pullRequest, $this->githubDeploymentService);
        $this->eventDispatcher->dispatch($event, SurvivalKitEvents::GITHUB_DEPLOYMENT);

        try {
            $githubDeploymentService = $event->getDeploymentService();
            $githubDeploymentService->deploy($event->getPullRequest());
            Logger::notice("[DeploymentController] Github webhook deployment succeeed", LogContext::DEPLOYMENT());
        } catch (GithubDeploymentException $e) {
            Logger::exception("[DeploymentController] Github webhook deployment failed", LogContext::DEPLOYMENT(), $e);

            return $this->json(["message" => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            Logger::exception("[DeploymentController] Github webhook deployment failed", LogContext::DEPLOYMENT(), $e);

            return $this->json(["message" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json("", Response::HTTP_NO_CONTENT);
    }

    /**
     * @inheritdoc
     */
    public function getToken() : string
    {
        return $this->getParameter('survival_kit.deployment.secret_token');
    }
}
