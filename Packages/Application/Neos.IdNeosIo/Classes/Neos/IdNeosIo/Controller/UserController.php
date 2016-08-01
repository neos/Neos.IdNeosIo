<?php
namespace Neos\IdNeosIo\Controller;

/*                                                          *
 * This script belongs to the Flow package "Neos.IdNeosIo". *
 *                                                          */

use Flownative\DoubleOptIn\Helper;
use Flownative\DoubleOptIn\Token;
use Neos\CrowdClient\Domain\Repository\UserRepository;
use Neos\CrowdClient\Domain\Service\CrowdClient;
use Neos\IdNeosIo\Domain\Model\UserDto;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;

class UserController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var string
	 * @Flow\Inject(setting="crowdApplicationName")
	 */
	protected $crowdApplicationName;

	/**
	 * @var string
	 * @Flow\Inject(setting="crowdApplicationPassword")
	 */
	protected $crowdApplicationPassword;

	/**
	 * @var CrowdClient
	 */
	protected $crowdClient;

	/**
	 * @var Helper
	 * @Flow\Inject
	 */
	protected $doubleOptInHelper;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var UserRepository
	 * @Flow\Inject
	 */
	protected $userRepository;

	/**
	 * @return void
	 */
	public function initializeObject() {
		$this->crowdClient = new CrowdClient($this->crowdApplicationName, $this->crowdApplicationPassword);
	}

	/**
	 * @return void
	 */
	public function indexAction() {
		$account = $this->securityContext->getAccount();
		$this->view->assign('account', $account);
		$this->view->assign('user', $this->userRepository->findOneByAccount($account));
	}

	/**
	 * @return void
	 */
	public function newAction() {
	}

	/**
	 * @param UserDto $user
	 * @return void
	 */
	public function sendActivationEmailAction(UserDto $user) {
		$token = $this->doubleOptInHelper->generateToken($user->getEmail(), 'id.neos.io registration', ['user' => $user]);
		$this->doubleOptInHelper->setRequest($this->request);
		$this->doubleOptInHelper->sendActivationMail($user->getEmail(), $token);

		$this->addFlashMessage('We\'ve sent you an email with a link to activate your account!', '', Message::SEVERITY_OK);
		$this->redirect('index');
	}

	/**
	 * @param Token $token
	 * @return void
	 */
	public function createAction(Token $token = NULL) {
		if ($token === NULL) {
			$this->addFlashMessage('The activation link is not valid.', '', Message::SEVERITY_ERROR);
			$this->forward('createError');
		}

		$user = $token->getMeta()['user'];
		$result = $this->crowdClient->addUser($user->getFirstname(), $user->getLastname(), $user->getEmail(), $user->getUsername(), $user->getPassword());
		if (!$result->hasErrors()) {
			$this->addFlashMessage('Your account was created successfully. You can now sing in with your credentials.', 'Account created', Message::SEVERITY_OK);
			$this->redirect('index');
		} else {
			$error = $result->getFirstError();
			$this->addFlashMessage($error->getMessage(), $error->getTitle(), Message::SEVERITY_ERROR);
			$this->forward('createError');
		}
	}

	/**
	 * @return void
	 */
	public function createErrorAction() {

	}

	/**
	 * A template method for displaying custom error flash messages, or to
	 * display no flash message at all on errors. Override this to customize
	 * the flash message in your action controller.
	 *
	 * @return \TYPO3\Flow\Error\Message The flash message or FALSE if no flash message should be set
	 * @api
	 */
	protected function getErrorFlashMessage() {
		return FALSE;
	}
}