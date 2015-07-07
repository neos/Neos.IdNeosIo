<?php
namespace Neos\IdNeosIo\Controller;

/*                                                                         *
 * This script belongs to the TYPO3 Flow package "Neos.IdNeosIo".          *
 *                                                                         */

use TYPO3\Flow\Annotations as Flow;

class LoginController extends \TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController {

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * {@inheritdoc}
	 */
	public function logoutAction() {
		$this->authenticationManager->logout();
		$this->redirect('index', 'User');
	}

	/**
	 * {@inheritdoc}
	 */
	protected function onAuthenticationSuccess(\TYPO3\Flow\Mvc\ActionRequest $originalRequest = NULL) {
		if ($originalRequest !== NULL) {
			$this->redirectToRequest($originalRequest);
		}

		$this->redirect('index', 'User');
	}

	/**
	 * {@inheritdoc}
	 */
	protected function onAuthenticationFailure(\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception = NULL) {
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getErrorFlashMessage() {
		return new \TYPO3\Flow\Error\Error('Please check your username and password', NULL, array(), 'Authentication failed');
	}
}