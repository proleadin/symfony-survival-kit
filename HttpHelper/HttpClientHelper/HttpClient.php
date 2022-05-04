<?php declare(strict_types=1);

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
    private const DEFAULT_REQUEST_TIMEOUT = 30.00;

    private ClientInterface $httpClient;

    public function setHttpClient(ClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @throws HttpClientException
     */
    public function get(string $sUrl, array $aRequestOptions, string $sAction, LogContext $logContext, array $aLogMetadata = []): ResponseInterface
    {
        return $this->request(Request::METHOD_GET, $sUrl, $aRequestOptions, $sAction, $logContext, $aLogMetadata);
    }

    /**
     * @throws HttpClientException
     */
    public function post(string $sUrl, array $aRequestOptions, string $sAction, LogContext $logContext, array $aLogMetadata = []): ResponseInterface
    {
        return $this->request(Request::METHOD_POST, $sUrl, $aRequestOptions, $sAction, $logContext, $aLogMetadata);
    }

    /**
     * @throws HttpClientException
     */
    public function put(string $sUrl, array $aRequestOptions, string $sAction, LogContext $logContext, array $aLogMetadata = []): ResponseInterface
    {
        return $this->request(Request::METHOD_PUT, $sUrl, $aRequestOptions, $sAction, $logContext, $aLogMetadata);
    }

    /**
     * @throws HttpClientException
     */
    public function patch(string $sUrl, array $aRequestOptions, string $sAction, LogContext $logContext, array $aLogMetadata = []): ResponseInterface
    {
        return $this->request(Request::METHOD_PATCH, $sUrl, $aRequestOptions, $sAction, $logContext, $aLogMetadata);
    }

    private function request(
        string $sMethod,
        string $sUrl,
        array $aRequestOptions,
        string $sAction,
        LogContext $logContext,
        array $aLogMetadata = []
    ): ResponseInterface
    {
        try {
            $aRequestOptions[RequestOptions::ON_STATS] = function (\GuzzleHttp\TransferStats $stats) use (&$sEffectiveUri) {
                $sEffectiveUri = $stats->getEffectiveUri();
            };
            $aRequestOptions[RequestOptions::TIMEOUT] = $aRequestOptions[RequestOptions::TIMEOUT] ?? self::DEFAULT_REQUEST_TIMEOUT;

            $response = $this->httpClient->request($sMethod, $sUrl, $aRequestOptions);
            $sResponseBody = $response->getBody()->getContents();
            $response->getBody()->rewind();

            // some responses can have unsupported encoding
            // need to fix the response encoding in order to have logs properly added
            if (\function_exists('mb_detect_encoding') && !\mb_detect_encoding($sResponseBody, null, true)) {
                $sResponseBody = \utf8_encode($sResponseBody);
            }

            Logger::debug("$sAction : succeed requesting $sMethod $sEffectiveUri - {$response->getStatusCode()}", $logContext, \array_merge($aLogMetadata, [
                'response' => $sResponseBody,
                'requestOptions' => $this->cleanRequestOptions($aRequestOptions)
            ]));
        } catch (GuzzleException $e) {
            if (\method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $response = $e->getResponse();
                $sMessage = $response->getBody()->getContents();
                $iHttpCode = $response->getStatusCode();
            } else {
                $sMessage = $e->getMessage();
                $iHttpCode = $e->getCode();
            }

            Logger::error("$sAction : error while requesting $sMethod $sEffectiveUri - $iHttpCode", $logContext, \array_merge($aLogMetadata, [
                'response' => $sMessage,
                'requestOptions' => $this->cleanRequestOptions($aRequestOptions)
            ]));

            throw new HttpClientException($sMessage, $iHttpCode);

        } catch (\Throwable $e) {
            Logger::exception("$sAction : error while requesting $sMethod $sEffectiveUri", $logContext, $e, $aLogMetadata);

            throw $e;
        }

        return $response;
    }

    private function cleanRequestOptions(array $aRequestOptions): array
    {
        unset($aRequestOptions[RequestOptions::ON_STATS]);

        return $this->hideRequestSecrets($aRequestOptions);
    }

    private function hideRequestSecrets(array $aRequestOptions): array
    {
        // hide authorization header
        if (isset($aRequestOptions[RequestOptions::HEADERS]['Authorization'])) {
            $aRequestOptions[RequestOptions::HEADERS]['Authorization'] = '*****';
        }

        // hide basic authentication credentials
        if (isset($aRequestOptions[RequestOptions::AUTH])) {
            $aRequestOptions[RequestOptions::AUTH] = '*****';
        }

        // hide client certificate password
        if (isset($aRequestOptions[RequestOptions::CERT]) && \is_array($aRequestOptions[RequestOptions::CERT])) {
            if (isset($aRequestOptions[RequestOptions::CERT][1])) {
                $aRequestOptions[RequestOptions::CERT][1] = '*****';
            }
        }

        return $aRequestOptions;
    }
}
