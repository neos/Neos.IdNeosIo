<?php
namespace Neos\IdNeosIo\Controller;

/*                                                          *
 * This script belongs to the Flow package "Neos.IdNeosIo". *
 *                                                          */

use Neos\CrowdClient\Domain\Service\CrowdClient;
use TYPO3\Flow\Annotations as Flow;

class UserController extends \TYPO3\Flow\Mvc\Controller\ActionController {

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
	 * @return void
	 */
	public function newAction() {
	}

	/**
	 * @return void
	 */
	public function createAction() {
	}
}