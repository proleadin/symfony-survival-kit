<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\HttpHelper\HttpClientHelper;

use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\Logging\LogContext;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Wrapper for a GuzzleHttp Client
 * @see For the request options https://docs.guzzlephp.org/en/stable/request-options.html
 */
class HttpClient
{
    private const DEFAULT_CONNECT_TIMEOUT = 10.00;
    private const DEFAULT_REQUEST_TIMEOUT = 30.00;

    private ClientInterface $httpClient;

    public function setHttpClient(ClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @throws HttpClientException
     */
    public function get(
        string $sUrl,
        array $aReqOptions,
        string $sAction,
        LogContext $logContext,
        array $aLogData = [],
        bool $bErrorLogLevelForHttpRequest = true
    ): ResponseInterface {
        return $this->request(Request::METHOD_GET, $sUrl, $aReqOptions, $sAction, $logContext, $aLogData, $bErrorLogLevelForHttpRequest);
    }

    /**
     * @throws HttpClientException
     */
    public function post(
        string $sUrl,
        array $aReqOptions,
        string $sAction,
        LogContext $logContext,
        array $aLogData = [],
        bool $bErrorLogLevelForHttpRequest = true
    ): ResponseInterface {
        return $this->request(Request::METHOD_POST, $sUrl, $aReqOptions, $sAction, $logContext, $aLogData, $bErrorLogLevelForHttpRequest);
    }

    /**
     * @throws HttpClientException
     */
    public function put(
        string $sUrl,
        array $aReqOptions,
        string $sAction,
        LogContext $logContext,
        array $aLogData = [],
        bool $bErrorLogLevelForHttpRequest = true
    ): ResponseInterface {
        return $this->request(Request::METHOD_PUT, $sUrl, $aReqOptions, $sAction, $logContext, $aLogData, $bErrorLogLevelForHttpRequest);
    }

    /**
     * @throws HttpClientException
     */
    public function patch(
        string $sUrl,
        array $aReqOptions,
        string $sAction,
        LogContext $logContext,
        array $aLogData = [],
        bool $bErrorLogLevelForHttpRequest = true
    ): ResponseInterface {
        return $this->request(Request::METHOD_PATCH, $sUrl, $aReqOptions, $sAction, $logContext, $aLogData, $bErrorLogLevelForHttpRequest);
    }

    private function request(
        string $sMethod,
        string $sUrl,
        array $aReqOptions,
        string $sAction,
        LogContext $logContext,
        array $aLogData,
        bool $bErrorLogLevelForHttpRequest
    ): ResponseInterface {
        try {
            $aReqOptions[RequestOptions::ON_STATS] = function (\GuzzleHttp\TransferStats $stats) use (&$sEffectiveUri) {
                $sEffectiveUri = $stats->getEffectiveUri();
            };
            $aReqOptions[RequestOptions::TIMEOUT] = $aReqOptions[RequestOptions::TIMEOUT] ?? self::DEFAULT_REQUEST_TIMEOUT;
            $aReqOptions[RequestOptions::CONNECT_TIMEOUT] = $aReqOptions[RequestOptions::CONNECT_TIMEOUT] ?? self::DEFAULT_CONNECT_TIMEOUT;

            $response = $this->httpClient->request($sMethod, $sUrl, $aReqOptions);
            $sResponseBody = $response->getBody()->getContents();
            $response->getBody()->rewind();

            // some responses can have unsupported encoding
            // need to fix the response encoding in order to have logs properly added
            if (\function_exists('mb_detect_encoding') && !\mb_detect_encoding($sResponseBody, null, true)) {
                $sResponseBody = \utf8_encode($sResponseBody);
            }

            Logger::debug(
                "$sAction : succeed requesting $sMethod $sEffectiveUri - {$response->getStatusCode()}",
                $logContext,
                \array_merge($aLogData, [
                    'response' => $sResponseBody,
                    'requestOptions' => $this->cleanRequestOptions($aReqOptions)
                ])
            );
        } catch (GuzzleException $e) {
            if (\method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $response = $e->getResponse();
                $sMessage = $response->getBody()->getContents();
                $iHttpCode = $response->getStatusCode();
            } else {
                $sMessage = $e->getMessage();
                $iHttpCode = $e->getCode();
            }

            $sLogLevel = $bErrorLogLevelForHttpRequest ? 'error' : 'notice';

            Logger::$sLogLevel("$sAction : error while requesting $sMethod $sEffectiveUri - $iHttpCode", $logContext, \array_merge($aLogData, [
                'response' => $sMessage,
                'requestOptions' => $this->cleanRequestOptions($aReqOptions)
            ]));

            throw new HttpClientException($sMessage, $iHttpCode);
        } catch (\Throwable $e) {
            Logger::exception("$sAction : error while requesting $sMethod $sEffectiveUri", $logContext, $e, $aLogData);

            throw $e;
        }

        return $response;
    }

    private function cleanRequestOptions(array $aReqOptions): array
    {
        unset($aReqOptions[RequestOptions::ON_STATS]);

        return $this->hideRequestSecrets($aReqOptions);
    }

    private function hideRequestSecrets(array $aReqOptions): array
    {
        // hide authorization header
        if (isset($aReqOptions[RequestOptions::HEADERS])) {
            foreach ($aReqOptions[RequestOptions::HEADERS] as $sKey => $sValue) {
                if (\strtolower($sKey) === 'authorization' || \strpos(\strtolower($sKey), 'key') !== false) {
                    $aReqOptions[RequestOptions::HEADERS][$sKey] = '*****';
                }
            }
        }

        // hide basic authentication credentials
        if (isset($aReqOptions[RequestOptions::AUTH])) {
            $aReqOptions[RequestOptions::AUTH] = '*****';
        }

        // hide basic authentication credentials
        if (isset($aReqOptions[RequestOptions::SSL_KEY])) {
            $aReqOptions[RequestOptions::SSL_KEY] = '*****';
        }

        // hide client certificate password
        if (isset($aReqOptions[RequestOptions::CERT]) && \is_array($aReqOptions[RequestOptions::CERT])) {
            if (isset($aReqOptions[RequestOptions::CERT][1])) {
                $aReqOptions[RequestOptions::CERT][1] = '*****';
            }
        }

        return $aReqOptions;
    }
}
