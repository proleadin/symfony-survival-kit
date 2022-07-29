<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Command;

use Leadin\SurvivalKitBundle\Logging\LogContext;
use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\HttpHelper\HttpClientHelper\HttpClient;
use Leadin\SurvivalKitBundle\HttpHelper\HttpClientHelper\HttpClientException;

use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Allows to reset OPCache of the web server or PHP-FPM process from the command line
 */
class OPCacheResetCommand extends Command
{
    protected static $defaultName = 'ssk:opcache-reset';

    private string $sAuthorizationToken;
    private string $sAppHost;
    private HttpClient $httpClient;
    private UrlGeneratorInterface $router;

    public function __construct(string $sAppHost, string $sAuthorizationToken, HttpClient $httpClient, UrlGeneratorInterface $router)
    {
        parent::__construct();
        $this->sAppHost = $sAppHost;
        $this->sAuthorizationToken = $sAuthorizationToken;
        $this->httpClient = $httpClient;
        $this->router = $router;
    }

    protected function configure(): void
    {
        $this->setDescription('Reset OPCache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $sUrl = "http://{$this->sAppHost}" . $this->router->generate('survival_kit_internal_tools_opcache_reset');
            $aRequestOptions = [
                RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $this->sAuthorizationToken]
            ];
            $response = $this->httpClient->get($sUrl, $aRequestOptions, "[OPCacheResetCommand] OPCache reset", LogContext::INTERNAL_TOOLS());
            $sResponseBody = $response->getBody()->getContents();
            $iHttpCode = $response->getStatusCode();
            $decodedResponse = \json_decode($sResponseBody);
            if (!isset($decodedResponse->success) || false === $decodedResponse->success) {
                $io->error("OPCache reset failed: httpCode $iHttpCode | $sResponseBody");

                return Command::FAILURE;
            }
        } catch (HttpClientException $e) {
            $io->error("OPCache reset failed: httpCode: {$e->getCode()} | {$e->getMessage()}");

            return Command::FAILURE;
        }

        $io->success('OPCache reset succeed');

        return Command::SUCCESS;
    }
}
