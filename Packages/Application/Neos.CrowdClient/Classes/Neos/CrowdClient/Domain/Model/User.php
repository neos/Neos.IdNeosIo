<?php
namespace Neos\CrowdClient\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Neos.CrowdClient".      *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Account;
use Doctrine\ORM\Mapping as ORM;

/**
 * An entity representing a Crowd user
 *
 * @Flow\Entity
 */
class User {

	/**
	 * @var Account
	 * @ORM\ManyToOne
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $account;

	/**
	 * @var string
	 */
	protected $firstName;

	/**
	 * @var string
	 */
	protected $lastName;

	/**
	 * @var string
	 */
	protected $emailAddress;

	/**
	 * @param Account $account
	 * @param array $data The user data as returned from the Crowd API (e.g. ['first-name' => '<firstName>', 'last-name' => '<lastName>', ...])
	 */
	public function __construct(Account $account, array $data) {
		$this->account = $account;
		$this->sync($data);
	}

	/**
	 * Synchronize user data with data returned from the Crowd API
	 *
	 * @param array $data The user data as returned from the Crowd API (e.g. ['first-name' => '<firstName>', 'last-name' => '<lastName>', ...])
	 * @return void
	 */
	public function sync(array $data) {
		$this->syncProperty('first-name', $data, 'firstName');
		$this->syncProperty('last-name', $data, 'lastName');
		$this->syncProperty('email', $data, 'emailAddress');
	}

	/**
	 * Synchronize a particular property from the given $data
	 *
	 * @param string $propertyName The property name as used in the Crowd API (e.g. "first-name")
	 * @param array $data The user data as returned from the Crowd API
	 * @param string $alias Optional alias for the property for some basic mapping (e.g. "firstName")
	 * @return void
	 */
	protected function syncProperty($propertyName, $data, $alias = null) {
		if ($alias === null) {
			$alias = $propertyName;
		}
		if (isset($data[$propertyName]) && $data[$propertyName] !== $this->{$alias}) {
			$this->{$alias} = $data[$propertyName];
		}
	}

	/**
	 * @return Account
	 */
	public function getAccount() {
		return $this->account;
	}

	/**
	 * @return string
	 */
	public function getFirstName() {
		return $this->firstName;
	}

	/**
	 * @return string
	 */
	public function getLastName() {
		return $this->lastName;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->firstName . ' ' . $this->lastName;
	}

	/**
	 * @return string
	 */
	public function getEmailAddress() {
		return $this->emailAddress;
	}

}