<?php
namespace Neos\IdNeosIo\Controller;

/*                                                          *
 * This script belongs to the Flow package "Neos.IdNeosIo". *
 *                                                          */

use Neos\CrowdClient\Domain\Service\CrowdClient;
use TYPO3\Flow\Annotations as Flow;

class ResetPasswordController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var CrowdClient
	 * @Flow\Inject
	 */
	protected $crowdClient;

	/**
	 * @return void
	 */
	public function initializeObject() {
		$this->crowdClient = new CrowdClient('idneosio', 'Moo6yohp');
	}

	/**
	 * @return void
	 */
	public function indexAction() {
	}

	/**
	 * @param string $username
	 * @return void
	 */
	public function sendResetLinkAction($username) {
		if ($this->crowdClient->getUser($username) !== NULL) {
			//send mail with confirmation link
			return 'We\'ve sent an E-Mail with a password reset link';
		}
	}

	/**
	 * @return void
	 */
	public function resetFormAction($resetToken) {
		//validate resetToken
		//render reset form
	}

	/**
	 * @return void
	 */
	public function resetPasswordAction($newPassword, $newPasswordConfirmation) {
		//validate some token
		//reset password
	}
}