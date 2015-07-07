<?php
namespace Neos\IdNeosIo\Domain\Model;

/*                                                          *
 * This script belongs to the Flow package "Neos.IdNeosIo". *
 *                                                          */

use TYPO3\Flow\Annotations as Flow;

class UserDto {

	/**
	 * @Flow\Validate(type="NotEmpty")
	 * @var string
	 */
	protected $firstname;

	/**
	 * @Flow\Validate(type="NotEmpty")
	 * @var string
	 */
	protected $lastname;

	/**
	 * @Flow\Validate(type="EmailAddress")
	 * @var string
	 */
	protected $email;

	/**
	 * @Flow\Validate(type="StringLength", options={"minimum"=4})
	 * @var string
	 */
	protected $username;

	/**
	 * @Flow\Validate(type="StringLength", options={"minimum"=8})
	 * @var string
	 */
	protected $password;

	/**
	 * @var string
	 */
	protected $passwordConfirmation;

	/**
	 * @return string
	 */
	public function getFirstname() {
		return $this->firstname;
	}

	/**
	 * @param string $firstname
	 */
	public function setFirstname($firstname) {
		$this->firstname = $firstname;
	}

	/**
	 * @return string
	 */
	public function getLastname() {
		return $this->lastname;
	}

	/**
	 * @param string $lastname
	 */
	public function setLastname($lastname) {
		$this->lastname = $lastname;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @param string $email
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * @param string $username
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * @param string $password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * @return string
	 */
	public function getPasswordConfirmation() {
		return $this->passwordConfirmation;
	}

	/**
	 * @param string $passwordConfirmation
	 */
	public function setPasswordConfirmation($passwordConfirmation) {
		$this->passwordConfirmation = $passwordConfirmation;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return [
			'firstname' => $this->firstname,
			'lastname' => $this->lastname,
			'email' => $this->email,
			'username' => $this->username,
			'password' => $this->password
		];
	}
}
