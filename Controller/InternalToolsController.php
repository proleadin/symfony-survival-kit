<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Controller;

use Leadin\SurvivalKitBundle\Logging\LogContext;
use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\HttpHelper\HttpServerHelper\ITokenAuthenticatedController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/internal-tools", name="survival_kit_internal_tools_");
 */
class InternalToolsController extends AbstractController implements ITokenAuthenticatedController
{
    /**
     * @Route("/opcache-reset", name="opcache_reset", methods={"GET"})
     */
    public function opcacheReset(): Response
    {
        try {
            \opcache_reset();
            Logger::notice("[InternalToolsController] opCache reset succeed", LogContext::INTERNAL_TOOLS());
        } catch (\Throwable $e) {
            Logger::error("[InternalToolsController] opCache reset failed : {$e->getMessage()}", LogContext::INTERNAL_TOOLS());

            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response("", Response::HTTP_OK);
    }

    /**
     * @inheritdoc
     */
    public function getToken(): string
    {
        return $this->getParameter('kernel.secret');
    }
}
