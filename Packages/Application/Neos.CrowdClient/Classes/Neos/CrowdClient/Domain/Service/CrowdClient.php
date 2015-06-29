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
}