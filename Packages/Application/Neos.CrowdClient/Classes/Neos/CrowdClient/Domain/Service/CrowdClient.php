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
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

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
			'auth' => array($this->applicationName, $this->applicationPassword),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
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
		try {
			$response = $this->httpClient->post(rtrim($this->crowdBaseUri, '/') . '/rest/usermanagement/1/authentication?username=' . urlencode($username), array('body' => json_encode(array('value' => $password))));
			$responseData = json_decode($response->getBody()->getContents(), TRUE);

			return $responseData;
		} catch (ClientException $e) {
			$responseError = json_decode($e->getResponse()->getBody()->getContents());
			if (isset($responseError->reason)) {
				switch ($responseError->reason) {
					case 'INVALID_USER_AUTHENTICATION':
						return NULL;
					case 'USER_NOT_FOUND':
						return NULL;
				}
			}
			throw $e;
		}
	}

	/**
	 * @param string $username
	 * @return array The raw Crowd user data or NULL if the user was not found
	 */
	public function getUser($username) {
		try {
			$response = $this->httpClient->get(rtrim($this->crowdBaseUri, '/') . '/rest/usermanagement/1/user?username=' . urlencode($username));
			$responseData = json_decode($response->getBody()->getContents(), TRUE);

			return $responseData;
		} catch (ClientException $e) {
			if ($e->getResponse()->getStatusCode() === 404) {
				return NULL;
			}
			throw $e;
		}
	}

	/**
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $email
	 * @param string $username
	 * @param string $password
	 * @return \TYPO3\Flow\Error\Result
	 */
	public function addUser($firstname, $lastname, $email, $username, $password) {
		$result = new \TYPO3\Flow\Error\Result();

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
			$uri = rtrim($this->crowdBaseUri, '/') . '/rest/usermanagement/1/user';
			$response = $this->httpClient->post($uri, array('body' => json_encode($userData)));
			$responseData = json_decode($response->getBody()->getContents(), TRUE);
			// TODO Check response data?
		} catch (ClientException $e) {
			$responseError = json_decode($e->getResponse()->getBody()->getContents());
			if (isset($responseError->reason)) {
				switch ($responseError->reason) {
					case 'INVALID_USER':
						$result->addError(new \TYPO3\Flow\Error\Error($responseError->message));
						return $result;
				}
			}

			$userData['password']['value'] = '********';
			$this->systemLogger->logException($e, array(
				'uri' => $uri,
				'requestData' => $userData,
				'responseStatus' => $e->getResponse()->getStatusCode(),
				'responseBody' => $e->getResponse()->getBody()->getContents()
			));
			$result->addError(new \TYPO3\Flow\Error\Error('There was an unspecified error'));
		}
		return $result;
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @return mixed
	 */
	public function setPasswordForUser($username, $password) {
		try {
			$response = $this->httpClient->put(rtrim($this->crowdBaseUri, '/') . '/rest/usermanagement/1/user/password?username=' . urlencode($username), array('body' => json_encode(array('value' => $password))));
			$responseData = json_decode($response->getBody()->getContents(), TRUE);
			return $responseData;
		} catch (ClientException $e) {
			return FALSE;
		}
	}
}