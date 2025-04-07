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
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\IdNeosIo\Domain\Model\AddUserDto;
use Neos\IdNeosIo\Domain\Model\ChangeEmailDto;
use Neos\IdNeosIo\Domain\Model\ChangeNameDto;

class UserController extends ActionController
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
     * @var Context
     * @Flow\Inject
     */
    protected $securityContext;

    public function indexAction(): void
    {
        $account = $this->securityContext->getAccount();
        $this->view->assign('crowdUser', $this->crowdClient->getUser($account->getAccountIdentifier()));
    }

    public function newAction(): void
    {
    }

    /**
     * @param AddUserDto $addUser
     * @return void
     * @throws StopActionException | UnknownPresetException
     */
    public function sendActivationEmailAction(AddUserDto $addUser): void
    {
        $token = $this->doubleOptInHelper->generateToken($addUser->getUsername(), 'id.neos.io registration', ['addUser' => $addUser]);
        $this->doubleOptInHelper->setRequest($this->request);
        $this->doubleOptInHelper->sendActivationMail($addUser->getEmail(), $token);

        $this->addFlashMessage('We\'ve sent you an email to %s with a link to activate your account!', '', Message::SEVERITY_NOTICE, [$addUser->getEmail()]);
        $this->redirect('index');
    }

    /**
     * @param Token|null $token
     * @throws AccessDeniedException
     * @throws ForwardException
     * @throws StopActionException
     * @throws \JsonException
     */
    public function activateAction(Token $token = null): void
    {
        if ($token === null) {
            throw new AccessDeniedException('Missing token', 1536157328);
        }
        /** @var AddUserDto $addUser */
        $addUser = $token->getMeta()['addUser'];
        $result = $this->crowdClient->addUser($addUser->getFirstName(), $addUser->getLastName(), $addUser->getEmail(), $addUser->getUsername(), $addUser->getPassword());
        if ($result->hasErrors()) {
            $error = $result->getFirstError();
            $this->addFlashMessage($error->getMessage(), $error->getTitle(), Message::SEVERITY_ERROR);
            $this->forward('createError');
        }
        $this->doubleOptInHelper->invalidateToken($token);
        $this->addFlashMessage('Your account was created successfully. You can manage your account at id.neos.io. How about you try out your new Neos community account by exploring our forum at discuss.neos.io?', 'Account created');
        $this->redirect('index');
    }

    public function editNameAction(): void
    {
        $account = $this->securityContext->getAccount();
        $this->view->assign('crowdUser', $this->crowdClient->getUser($account->getAccountIdentifier()));
    }

    /**
     * @param ChangeNameDto $changeName
     * @throws StopActionException
     */
    public function updateNameAction(ChangeNameDto $changeName): void
    {
        $account = $this->securityContext->getAccount();

        $this->crowdClient->setNameForUser($account->getAccountIdentifier(), $changeName->getFirstName(), $changeName->getLastName());

        $this->addFlashMessage('Your profile has been updated!');
        $this->redirect('index');
    }

    public function editEmailAction(): void
    {
        $account = $this->securityContext->getAccount();
        $this->view->assign('crowdUser', $this->crowdClient->getUser($account->getAccountIdentifier()));
    }

    /**
     * @param ChangeEmailDto $changeEmail
     * @throws UnknownPresetException | StopActionException
     */
    public function sendConfirmEmailAction(ChangeEmailDto $changeEmail): void
    {
        $account = $this->securityContext->getAccount();
        $crowdUser = $this->crowdClient->getUser($account->getAccountIdentifier());

        $token = $this->doubleOptInHelper->generateToken($crowdUser->getName(), 'id.neos.io change email', ['crowdUser' => $crowdUser, 'newEmail' => $changeEmail->getEmail()]);
        $this->doubleOptInHelper->setRequest($this->request);
        $this->doubleOptInHelper->sendActivationMail($changeEmail->getEmail(), $token);
        $this->addFlashMessage('We\'ve sent you an email to %s with a link to confirm your new email address!', '', Message::SEVERITY_NOTICE, [$changeEmail->getEmail()]);

        $this->redirect('index');
    }

    /**
     * @param Token|null $token
     * @throws AccessDeniedException
     * @throws StopActionException
     */
    public function confirmEmailAction(Token $token = null): void
    {
        if ($token === null) {
            throw new AccessDeniedException('Missing token', 1536157334);
        }
        /** @var string $newEmail */
        $newEmail = $token->getMeta()['newEmail'];
        $this->crowdClient->setEmailForUser($token->getIdentifier(), $newEmail);
        $this->addFlashMessage('Your email address has been updated!');
        $this->redirect('index');
    }

    public function createErrorAction(): void
    {

    }

    protected function getErrorFlashMessage(): bool
    {
        return false;
    }
}
