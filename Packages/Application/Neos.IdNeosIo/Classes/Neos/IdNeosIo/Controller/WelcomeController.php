<?php
namespace Neos\IdNeosIo\Controller;

/*                                                                         *
 * This script belongs to the TYPO3 Flow package "Neos.IdNeosIo".          *
 *                                                                         */

use TYPO3\Flow\Annotations as Flow;

class WelcomeController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @return string
	 */
	public function welcomeAction() {
		$this->redirect('index', 'User');
	}
}