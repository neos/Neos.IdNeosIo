<?php
declare(strict_types=1);

namespace Neos\CrowdClient\Domain\Service;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Neos\CrowdClient\Domain\Dto\User;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\PolicyService;
use Psr\Log\LoggerInterface;

/**
 * Class CrowdClient
 */
class CrowdClient
{
    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    protected HttpClient $httpClient;

    /**
     * @var string $crowdBaseUri
     * @Flow\InjectConfiguration(path="crowdBaseUri")
     */
    protected $crowdBaseUri;

    protected string $applicationName;

    protected string $applicationPassword;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    public function __construct(string $applicationName, string $applicationPassword)
    {
        $this->applicationName = $applicationName;
        $this->applicationPassword = $applicationPassword;
    }

    public function initializeObject(): void
    {
        $this->httpClient = new HttpClient([
            'base_uri' => rtrim($this->crowdBaseUri, '/') . '/rest/usermanagement/1/',
            'auth' => [$this->applicationName, $this->applicationPassword],
            'headers' => [
                'User-Agent' => 'neos/crowdclient',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function authenticate(string $username, string $password): ?User
    {
        try {
            $response = $this->httpClient->post('authentication?username=' . urlencode($username), ['body' => json_encode(['value' => $password], JSON_THROW_ON_ERROR)]);
            $authenticatedUser = User::fromCrowdResponse($response->getBody()->getContents());
            $this->emitUserAuthenticated($authenticatedUser);
            return $authenticatedUser;
        } catch (ClientException $exception) {
            $responseError = json_decode($exception->getResponse()->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
            if (isset($responseError->reason)) {
                switch ($responseError->reason) {
                    case 'INVALID_USER_AUTHENTICATION':
                    case 'USER_NOT_FOUND':
                    case 'EXPIRED_CREDENTIAL':
                    case 'INACTIVE_ACCOUNT':
                        return null;
                }
            }
            throw $exception;
        }
    }

    /**
     * @param string $username
     * @return User|null The Crowd User DTO or NULL if the user was not found
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function getUser(string $username): ?User
    {
        try {
            $response = $this->httpClient->get('user?username=' . urlencode($username));
            return User::fromCrowdResponse($response->getBody()->getContents());
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() === 404) {
                return null;
            }
            throw $exception;
        }
    }

    public function addUser(string $firstName, string $lastName, string $emailAddress, string $username, string $password): Result
    {
        $result = new Result();

        try {
            $userData = [
                'name' => $username,
                'first-name' => $firstName,
                'last-name' => $lastName,
                'email' => $emailAddress,
                'password' => [
                    'value' => $password
                ],
                'active' => true
            ];
            $response = $this->httpClient->post('user', ['body' => json_encode($userData, JSON_THROW_ON_ERROR)]);
            $newUser = User::fromCrowdResponse($response->getBody()->getContents());
            $this->emitUserAdded($newUser);

        } catch (ClientException $exception) {
            $responseError = json_decode($exception->getResponse()->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
            if (isset($responseError->reason) && $responseError->reason === 'INVALID_USER') {
                $result->addError(new Error($responseError->message));
                return $result;
            }

            $userData['password']['value'] = '********';
            $this->systemLogger->error($exception->getMessage(), [
                'uri' => 'user',
                'requestData' => $userData,
                'responseStatus' => $exception->getResponse()->getStatusCode(),
                'responseBody' => $exception->getResponse()->getBody()->getContents()
            ]);
            $result->addError(new Error('There was an unspecified error'));
        }
        return $result;
    }

    public function setPasswordForUser(string $username, string $password): void
    {
        $this->httpClient->put('user/password?username=' . urlencode($username), ['body' => json_encode(['value' => $password], JSON_THROW_ON_ERROR)]);
    }

    public function setNameForUser(string $username, string $firstName, string $lastName): void
    {
        $this->updateUser($username, ['first-name' => $firstName, 'last-name' => $lastName]);
    }

    public function setEmailForUser(string $username, string $email): void
    {
        $this->updateUser($username, ['email' => $email]);
    }

    private function updateUser(string $username, array $newValues): void
    {
        $user = $this->getUser($username);
        if ($user === null) {
            throw new \InvalidArgumentException(sprintf('User "%s" was not found!', $username), 1536160352);
        }
        $defaultValues = [
            'active' => true,
        ];
        $data = array_merge($defaultValues, $user->toArray(), $newValues);
        $this->httpClient->put('user?username=' . urlencode($username), ['body' => json_encode($data, JSON_THROW_ON_ERROR)]);
        $this->emitUserUpdated($user, $newValues);
    }

    /**
     * Signals that a user has been authenticated
     *
     * @param User $user
     * @return void
     * @Flow\Signal
     */
    protected function emitUserAuthenticated(User $user): void
    {
    }

    /**
     * Signals that a user has been added
     *
     * @param User $user
     * @return void
     * @Flow\Signal
     */
    protected function emitUserAdded(User $user): void
    {
    }

    /**
     * Signals that a user profile has been updated
     *
     * @param User $user
     * @param array $newValues
     * @return void
     * @Flow\Signal
     */
    protected function emitUserUpdated(User $user, array $newValues): void
    {
    }
}
