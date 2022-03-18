<?php

namespace Leadin\SurvivalKitBundle\Controller;

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
class DebugManagerController extends AbstractController
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @Route("/", name="manage", methods={"GET"})
     */
    public function manage(Request $request): Response
    {
        $aContexts = [];
        $aConfig = $this->getConfig();
        $sLogContextEnum = $this->parameterBag->get('survival_kit.monolog.debug_manager.log_context_enum');
        $aLogContexts = $sLogContextEnum::toArray();
        \sort($aLogContexts);
        foreach ($aLogContexts as $sContext) {
            $aContexts[] = [
                "name" => $sContext,
                "expiration" => $aConfig[$sContext] ?? 0
            ];
        }

        return $this->render('@SurvivalKit/debug_manager/manage.twig', [
            "contexts" => $aContexts
        ]);
    }

    /**
     * @Route("/update-config/{sContext}/{sExpiration}", name="update_config", methods={"GET"})
     */
    public function updateConfig(string $sContext, string $sExpiration): Response
    {
        try {
            // TODO move code from src, put config in resources, investigate parameter bag

            $aConfig = $this->getConfig();
            $aConfig[$sContext] = $sExpiration;

            $sConfigPath = $this->parameterBag->get('survival_kit.monolog.debug_manager.config');
            $sDirPath = \dirname($sConfigPath);
            if (!\is_dir($sDirPath)) {
                \mkdir($sDirPath, 0777, true);
            }
            \file_put_contents($sConfigPath, \json_encode($aConfig), LOCK_EX);
        } catch (\Throwable $e) {
            throw new \Exception("Cannot update config file : {$e->getMessage()}");
        }

        return new Response("", Response::HTTP_NO_CONTENT);
    }

    private function getConfig(): array
    {
        $sConfigJson = @\file_get_contents($this->parameterBag->get('survival_kit.monolog.debug_manager.config')) ? : "";

        return \json_decode($sConfigJson, true) ? : [];
    }
}
