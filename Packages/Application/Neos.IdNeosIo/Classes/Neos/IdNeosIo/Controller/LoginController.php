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
	 * Is called if authentication was successful. If there has been an
	 * intercepted request due to security restrictions, it redirects to
	 * this, otherwise redirects to the user's profile page.
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there was none
	 * @return string
	 */
	protected function onAuthenticationSuccess(\TYPO3\Flow\Mvc\ActionRequest $originalRequest = NULL) {
		if ($originalRequest !== NULL) {
			$this->redirectToRequest($originalRequest);
		}

		$this->redirect('index', 'User');
	}
}