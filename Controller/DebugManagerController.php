<?php

namespace Leadin\SurvivalKitBundle\Controller;

use Leadin\SurvivalKitBundle\HttpHelper\HttpServerHelper\ITokenAuthenticatedController;
use Leadin\SurvivalKitBundle\Logging\DebugManagerConfigStorage;
use Leadin\SurvivalKitBundle\Logging\LogContext;
use Leadin\SurvivalKitBundle\Logging\Logger;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/debug-manager", name="survival_kit_debug_manager_")
 */
class DebugManagerController extends AbstractController implements ITokenAuthenticatedController
{
    private DebugManagerConfigStorage $configStorage;

    public function __construct(DebugManagerConfigStorage $configStorage)
    {
        $this->configStorage = $configStorage;
    }

    /**
     * @Route("/", name="manage", methods={"GET"})
     */
    public function manage(Request $request): Response
    {
        $aContexts = [];
        $aConfig = $this->configStorage->getConfig();
        $sLogContextEnum = $this->getParameter('survival_kit.monolog.debug_manager.log_context_enum');
        $aLogContexts = $sLogContextEnum::toArray();
        \sort($aLogContexts);
        foreach ($aLogContexts as $sContext) {
            $aContexts[] = [
                "name" => $sContext,
                "expiration" => $aConfig[$sContext] ?? 0
            ];
        }

        return $this->render('@SurvivalKit/debug_manager/manage.twig', [
            "contexts" => $aContexts,
            "apiKey" => $this->getToken()
        ]);
    }

    /**
     * @Route("/update-config/{sContext}/{sExpiration}", name="update_config", methods={"GET"})
     */
    public function updateConfig(string $sContext, string $sExpiration): Response
    {
        try {
            $this->configStorage->updateConfig($sContext, $sExpiration);
        } catch (\Throwable $e) {
            return new Response("Cannot update config file : {$e->getMessage()}", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response("", Response::HTTP_NO_CONTENT);
    }

    /**
     * @inheritdoc
     */
    public function getToken() : string
    {
        return $this->getParameter('survival_kit.monolog.debug_manager.api_key');
    }
}
