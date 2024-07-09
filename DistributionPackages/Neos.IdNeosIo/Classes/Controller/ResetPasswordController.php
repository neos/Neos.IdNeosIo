<?php
declare(strict_types=1);

namespace Neos\IdNeosIo\Controller;

use Flownative\DoubleOptIn\Helper;
use Flownative\DoubleOptIn\Token;
use Flownative\DoubleOptIn\UnknownPresetException;
use Neos\CrowdClient\Domain\Service\CrowdClient;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Security\Context;
use Neos\IdNeosIo\Domain\Model\ResetPasswordDto;

class ResetPasswordController extends ActionController
{
    /**
     * @Flow\Inject
     * @var CrowdClient
     */
    protected $crowdClient;

    /**
     * @Flow\Inject
     * @var Helper
     */
    protected $doubleOptInHelper;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    public function indexAction(string $username = ''): void
    {
        $this->view->assign('username', $username);
    }

    /**
     * @throws UnknownPresetException | ForwardException | StopActionException
     */
    public function sendResetLinkAction(string $username): void
    {
        $crowdUser = $this->crowdClient->getUser($username);
        if ($crowdUser === null) {
            $this->addFlashMessage('The given username was not found. Please check your spelling or create a new account.',
                'User not found', Message::SEVERITY_ERROR);
            $this->forward('index', null, null, ['username' => $username]);
        }
        $token = $this->doubleOptInHelper->generateToken($crowdUser->getName(), 'id.neos.io reset password', ['crowdUser' => $crowdUser]);
        $this->doubleOptInHelper->setRequest($this->request);
        $this->doubleOptInHelper->sendActivationMail($crowdUser->getEmail(), $token);

        $this->addFlashMessage('We\'ve sent you an email with a link to reset your password.', '', Message::SEVERITY_NOTICE);
        $this->redirect('login', 'Login');
    }

    public function formAction(Token $token = null): void
    {
        if ($token !== null) {
            $this->view->assign('tokenHash', $token->getHash());
        }
    }

    /**
     * @throws StopActionException
     * @throws \JsonException
     * @Flow\SkipCsrfProtection
     */
    public function resetAction(ResetPasswordDto $resetPassword, Token $token = null): void
    {
        $this->crowdClient->setPasswordForUser($this->securityContext->getAccount()->getAccountIdentifier(), $resetPassword->getPassword());
        if ($token !== null) {
            $this->doubleOptInHelper->invalidateToken($token);
        }
        $this->addFlashMessage('Your password has been updated!');
        $this->redirect('login', 'Login');
    }

    protected function getErrorFlashMessage(): bool
    {
        return false;
    }
}
