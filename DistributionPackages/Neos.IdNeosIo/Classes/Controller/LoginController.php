<?php
declare(strict_types=1);

namespace Neos\IdNeosIo\Controller;

use Neos\DiscourseCrowdSso\DiscourseService;
use Neos\Error\Messages\Error;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Security\Authentication\Controller\AbstractAuthenticationController;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;

class LoginController extends AbstractAuthenticationController
{
    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var DiscourseService
     */
    protected $discourseService;

    /**
     * @throws StopActionException
     */
    public function logoutAction(): void
    {
        $account = $this->securityContext->getAccount();
        if ($account !== null) {
            try {
                $this->discourseService->logoutUser($account);
            } catch (\RuntimeException $exception) {
                $this->logger->debug(sprintf('Could not log out discourse user for account "%s", exception: %s', $account->getAccountIdentifier(), $exception->getMessage()));
            }
        }
        parent::logoutAction();
        $this->redirect('index', 'User');
    }

    /**
     * @throws StopActionException
     */
    protected function onAuthenticationSuccess(ActionRequest $originalRequest = null): void
    {
        if ($originalRequest !== null) {
            $this->redirectToRequest($originalRequest);
        }

        $this->redirect('index', 'User');
    }

    protected function onAuthenticationFailure(AuthenticationRequiredException $exception = null): void
    {
    }

    protected function getErrorFlashMessage(): Error
    {
        return new Error('Please check your username and password', null, [], 'Authentication failed');
    }
}
