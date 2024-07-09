<?php

namespace Neos\IdNeosIo\Controller;

use Neos\CrowdClient\Domain\Service\CrowdClient;
use Neos\DiscourseCrowdSso\DiscourseService;
use Neos\DiscourseCrowdSso\SsoPayload;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException;
use Neos\Flow\Security\Context;

class DiscourseLoginController extends ActionController
{

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var CrowdClient
     */
    protected $crowdClient;

    /**
     * @Flow\Inject
     * @var DiscourseService
     */
    protected $discourseService;

    /**
     * @param string $sso
     * @param string $sig
     * @return void
     * @throws StopActionException | UnsupportedRequestTypeException
     * @Flow\SkipCsrfProtection
     */
    public function authenticateAction(string $sso = '', string $sig = ''): void
    {
        if ($sso === '' && $sig === '') {
            $argumentsOfInterceptedRequest = $this->securityContext->getInterceptedRequest()->getArguments();
            if (!isset($argumentsOfInterceptedRequest['sso'], $argumentsOfInterceptedRequest['sig'])) {
                throw new \RuntimeException('This page needs to be called with valid sso and sig arguments from discourse!', 1534422436);
            }
            $sso = $argumentsOfInterceptedRequest['sso'];
            $sig = $argumentsOfInterceptedRequest['sig'];
        }
        $currentAccount = $this->securityContext->getAccount();

        $crowdUser = $this->crowdClient->getUser($currentAccount->getAccountIdentifier());
        $customFields = [
            'email' => $crowdUser->getEmail(),
            'name' => $crowdUser->getFullName(),
        ];
        $payload = SsoPayload::fromAccount($currentAccount, $customFields);
        try {
            $this->discourseService->synchronizeUser($payload);
        } catch (\RuntimeException $exception) {
            $exceptionMessage = sprintf('%s (%d)', $exception->getMessage(), $exception->getCode());
            if ($exception->getPrevious() !== null) {
                $exceptionMessage .= sprintf(' -> %s (%d)', $exception->getPrevious()->getMessage(), $exception->getPrevious()->getCode());
            }
            $this->logger->error('Could not synchronize user to discourse', ['exception' => $exceptionMessage]);
        }
        $redirectUri = $this->discourseService->authenticate($sso, $sig, $payload);
        $this->redirectToUri((string)$redirectUri, 0, 302);
    }
}
