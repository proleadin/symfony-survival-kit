<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\HttpHelper\HttpClientHelper;

class HttpClientResponse
{
    private string $sResponseBody;
    private int $iHttpCode;

    public function __construct(string $sResponseBody, int $iHttpCode)
    {
        $this->sResponseBody = $sResponseBody;
        $this->iHttpCode = $iHttpCode;
    }

    public function getResponseBody(): string
    {
        return $this->sResponseBody;
    }

    public function getHttpCode(): int
    {
        return $this->iHttpCode;
    }
}
