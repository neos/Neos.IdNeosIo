<?php
namespace Neos\CrowdClient\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Neos.CrowdClient".      *
 *                                                                        *
 *                                                                        */

use GuzzleHttp\Exception\ClientException;
use TYPO3\Flow\Annotations as Flow;
use GuzzleHttp\Client as HttpClient;

/**
 * Class CrowdClient
 */
class CrowdClient {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var HttpClient
	 */
	protected $httpClient;

	/**
	 * @var string $crowdBaseUri
	 * @Flow\Inject(setting="crowdBaseUri")
	 */
	protected $crowdBaseUri;

	/**
	 * @var string
	 */
	protected $applicationName;

	/**
	 * @var string
	 */
	protected $applicationPassword;

	/**
	 * Constructor
	 *
	 * @param string $applicationName
	 * @param string $applicationPassword
	 */
	public function __construct($applicationName, $applicationPassword) {
		$this->applicationName = $applicationName;
		$this->applicationPassword = $applicationPassword;
	}

	/**
	 * Initializes this Crowd Client
	 *
	 * @return void
	 */
	public function initializeObject() {
		$httpDefaultConfiguration = array(
				'base_uri' => rtrim($this->crowdBaseUri, '/') . '/rest/usermanagement/1/',
				'auth'    => array($this->applicationName, $this->applicationPassword),
				'headers' => array(
						'Content-Type' => 'application/json',
						'Accept'       => 'application/json'
				)
		);
		$this->httpClient = new HttpClient($httpDefaultConfiguration);
	}

	/**
	 * @param $username
	 * @param $password
	 * @return array|NULL
	 */
	public function authenticate($username, $password) {
		//TODO: check if sanitizing of $username and $password is enough
		try {
			$response = $this->httpClient->post(rtrim($this->crowdBaseUri, '/') . '/rest/usermanagement/1/authentication?username=' . urlencode($username), array('body' => json_encode(array('value' => $password))));
			$responseData = json_decode($response->getBody()->getContents(), TRUE);

			return $responseData;
		} catch (ClientException $e) {
			$responseError = json_decode($e->getResponse()->getBody()->getContents());
			switch ($responseError->reason) {
				case 'INVALID_USER_AUTHENTICATION':
					return NULL;
					break;
				default:
					throw $e;
			}
		}
	}

	/**
	 * @param $username
	 * @return mixed
	 */
	public function getUser($username) {
		//TODO: check if sanitizing of $username is enough
		try {
			$response = $this->httpClient->get(rtrim($this->crowdBaseUri, '/') . '/rest/usermanagement/1/user?username=' . urlencode($username));
			$responseData = json_decode($response->getBody()->getContents(), TRUE);

			return $responseData;
		} catch (ClientException $e) {
			//TODO: handle different exceptions correctly
			var_dump($e->getResponse()->getBody()->getContents());
			throw $e;
		}
	}

	/**
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $email
	 * @param string $username
	 * @param string $password
	 * @return mixed
	 */
	public function addUser($firstname, $lastname, $email, $username, $password) {
		//TODO: check if sanitizing is enough
		try {
			$userData = [
				'name' => $username,
				'first-name' => $firstname,
				'last-name' => $lastname,
				'email' => $email,
				'password' => [
					'value' => $password
				],
				'active' => TRUE
			];
			$response = $this->httpClient->post(rtrim($this->crowdBaseUri, '/') . '/rest/usermanagement/1/user', array('body' => json_encode($userData)));
			$responseData = json_decode($response->getBody()->getContents(), TRUE);
			return $responseData;
		} catch (ClientException $e) {
			return FALSE;
		}
	}

	/**
	 * @param string $username
	 * @param $password
	 * @return mixed
	 */
	public function setPasswordForUser($username, $password) {
		//TODO: check if sanitizing is enough
		try {
			$response = $this->httpClient->put(rtrim($this->crowdBaseUri, '/') . '/rest/usermanagement/1/user/password?username=' . urlencode($username), array('body' => json_encode(array('value' => $password))));
			$responseData = json_decode($response->getBody()->getContents(), TRUE);
			return $responseData;
		} catch (ClientException $e) {
			return FALSE;
		}
	}
}