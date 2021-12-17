<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\HttpHelper\HttpClientHelper;

use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\Logging\LogContext;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\Request;

class HttpClient
{
    private const DEFAULT_REQUEST_TIMEOUT = 30.00;
    
    private ClientInterface $httpClient;

    /**
     * @required
     */
    public function init(ClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    public function post(string $sUrl, array $aRequestOptions, float $fRequestTimeout = null): HttpClientResponse
    {
        return $this->request(Request::METHOD_POST, $sUrl, $aRequestOptions, $fRequestTimeout);
    }

    public function get(string $sUrl, array $aRequestOptions, float $fRequestTimeout = null): HttpClientResponse
    {
        return $this->request(Request::METHOD_GET, $sUrl, $aRequestOptions, $fRequestTimeout);
    }

    public function patch(string $sUrl, array $aRequestOptions, float $fRequestTimeout = null): HttpClientResponse
    {
        return $this->request(Request::METHOD_PATCH, $sUrl, $aRequestOptions, $fRequestTimeout);
    }

    public function logRequestAndResponse(LogContext $logContext, string $sUrl, string $sAction, IResponse $response, IRequest $request = null, array $aLogMetadata = []): void
    {
        $aLogMetadata['url'] = $sUrl;
        $aLogMetadata['httpCode'] = $response->getHttpCode();
        $aLogMetadata['response'] = $response->getResponse();
        $request && $aLogMetadata['request'] = $request->getBody();

        if (!$response->isSuccess()) {
            Logger::error("$sAction has failed: {$response->getErrorMessage()}", $logContext, $aLogMetadata);
        } else {
            Logger::debug("$sAction succeed", $logContext, $aLogMetadata);
        }
    }

    private function request(string $sMethod, string $sUrl, array $aRequestOptions, $fRequestTimeout = null): HttpClientResponse
    {
        $bError = true;
        try {
            $aRequestOptions[RequestOptions::TIMEOUT] = $fRequestTimeout ? : self::DEFAULT_REQUEST_TIMEOUT;
            $response = $this->httpClient->request($sMethod, $sUrl, $aRequestOptions);
            $sResponseBody = $response->getBody()->getContents();
            $iHttpCode = $response->getStatusCode();
            $bError = false;
        } catch (ConnectException $e) {
            $sResponseBody = "ConnectException: {$e->getMessage()}";
            $iHttpCode = 0;
        } catch (GuzzleException $e) {
            if (\method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $response = $e->getResponse();
                $sResponseBody = $response->getBody()->getContents();
                $iHttpCode = $response->getStatusCode();
            } else {
                $sResponseBody = "GuzzleException: {$e->getMessage()}";
                $iHttpCode = 0;
            }
        }

        $sLogLevel = $bError ? 'error' : 'debug';
        $sLogMessage = $bError ? 'Error while requesting %s %d - %s' : 'Response of requesting %s %d - %s';
        Logger::$sLogLevel(\sprintf($sLogMessage, $sMethod, $iHttpCode, $sUrl), LogContext::DEFAULT(), [
            'request' => $aRequestOptions,
            'response' => $sResponseBody
        ]);

        return new HttpClientResponse($sResponseBody, $iHttpCode);
    }
}
