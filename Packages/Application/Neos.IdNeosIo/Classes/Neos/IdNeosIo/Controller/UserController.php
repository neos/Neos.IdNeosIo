<?php
namespace Neos\IdNeosIo\Controller;

/*                                                          *
 * This script belongs to the Flow package "Neos.IdNeosIo". *
 *                                                          */

use Flownative\DoubleOptIn\Token;
use Flownative\DoubleOptIn\Helper;
use Neos\CrowdClient\Domain\Service\CrowdClient;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Party\Domain\Service\PartyService;

class UserController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var string $crowdApplicationName
	 * @Flow\Inject(setting="crowdApplicationName")
	 */
	protected $crowdApplicationName;

	/**
	 * @var string $crowdApplicationPassword
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
	 * @var PartyService
	 * @Flow\Inject
	 */
	protected $partyService;

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
		$this->view->assign('person', $this->partyService->getAssignedPartyOfAccount($account));
	}

	/**
	 * @return void
	 */
	public function newAction() {
	}

	/**
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $email
	 * @param string $username
	 * @param string $password
	 * @param string $passwordConfirmation
	 * @return void
	 */
	public function sendActivationEmailAction($firstname, $lastname, $email, $username, $password, $passwordConfirmation) {
		if ($password === '' || $password !== $passwordConfirmation) {
			$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error('Passwords didn\'t match!', 1435750717));
			return $this->errorAction();
		}

		$userData = [
			'firstname' => $firstname,
			'lastname' => $lastname,
			'email' => $email,
			'username' => $username,
			'password' => $password
		];

		$token = $this->doubleOptInHelper->generateToken($email, 'id.neos.io registration', $userData);
		$this->doubleOptInHelper->setRequest($this->request);
		$this->doubleOptInHelper->sendActivationMail($email, $token);

		$this->redirect('activationMailSent');
	}

	/**
	 * @return void
	 */
	public function activationMailSentAction() {
	}

	/**
	 * @param Token
	 * @return void
	 */
	public function createAction(Token $token) {
		$userData = $token->getMeta();
		if ($this->crowdClient->addUser($userData['firstname'], $userData['lastname'], $userData['email'], $userData['username'], $userData['password']) !== FALSE) {
			//TODO: show nice success message
			$this->redirect('index');
		}
		//TODO: Error
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