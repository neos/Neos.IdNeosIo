<?php
namespace Neos\IdNeosIo\Security\RequestPattern;

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Security\RequestPatternInterface;

/**
 * A request pattern that matches requests without tokens
 */
class NoTokenRequestPattern implements RequestPatternInterface
{

    public function matchRequest(RequestInterface $request): bool
    {
        if (!$request instanceof ActionRequest) {
            return false;
        }
        $httpRequest = $request->getHttpRequest();
        return !isset($httpRequest->getQueryParams()['token']);
    }
}
