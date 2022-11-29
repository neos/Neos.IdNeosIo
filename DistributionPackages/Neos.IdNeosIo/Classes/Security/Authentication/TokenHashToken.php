<?php
namespace Neos\IdNeosIo\Security\Authentication;

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\Token\AbstractToken;
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Exception\InvalidAuthenticationStatusException;

/**
 * A Flow authentication token representing a Flownative.DoubleOptIn Token
 */
class TokenHashToken extends AbstractToken implements SessionlessTokenInterface {

    /**
     * @param ActionRequest $actionRequest
     * @throws InvalidAuthenticationStatusException
     */
    public function updateCredentials(ActionRequest $actionRequest): void
    {
        $httpRequest = $actionRequest->getHttpRequest();
        if (!$httpRequest->hasArgument('token')) {
            return;
        }
        $this->credentials = ['tokenHash' => $httpRequest->getArgument('token')];
        $this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
    }
}
