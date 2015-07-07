<?php
namespace Neos\IdNeosIo\Domain\Validator;

/*                                                          *
 * This script belongs to the Flow package "Neos.IdNeosIo". *
 *                                                          */

use Neos\CrowdClient\Domain\Service\CrowdClient;
use Neos\IdNeosIo\Domain\Model\UserDto;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Error;

class UserDtoValidator extends \TYPO3\Flow\Validation\Validator\AbstractValidator {

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
	 * @return void
	 */
	public function initializeObject() {
		$this->crowdClient = new CrowdClient($this->crowdApplicationName, $this->crowdApplicationPassword);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function isValid($value) {
		if (!$value instanceof UserDto) {
			$this->addError('Value must be of type UserDto', 1436254418);
		}

		if ($value->getPassword() !== $value->getPasswordConfirmation()) {
			$this->result->forProperty('passwordConfirmation')->addError(new Error('Password does not match confirmation', 1436254655));
		}

		if ($this->crowdClient->getUser($value->getUsername()) !== NULL) {
			$this->result->forProperty('username')->addError(new Error('The chosen username is not available', 1436267801));
		}
	}

}
