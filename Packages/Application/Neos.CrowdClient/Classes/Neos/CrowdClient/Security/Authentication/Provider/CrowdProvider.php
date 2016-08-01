<?php
namespace Neos\CrowdClient\Security\Authentication\Provider;

/*                                                                         *
 * This script belongs to the TYPO3 Flow package "Neos.DiscourseCrowdSso". *
 *                                                                         *
 *                                                                         */

use Neos\CrowdClient\Domain\Model\User;
use Neos\CrowdClient\Domain\Repository\UserRepository;
use Neos\CrowdClient\Domain\Service\CrowdClient;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authentication\Provider\AbstractProvider;
use TYPO3\Flow\Security\Authentication\Token\UsernamePassword;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException;
use TYPO3\Flow\Security\Policy\PolicyService;

/**
 * An authentication provider that authenticates a username and password against
 * an atlassian crowd instance.
 */
class CrowdProvider extends AbstractProvider {

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\Flow\Security\AccountRepository
	 * @Flow\Inject
	 */
	protected $accountRepository;

	/**
	 * @var PolicyService
	 * @Flow\Inject
	 */
	protected $policyService;

	/**
	 * @Flow\Inject
	 * @var UserRepository
	 */
	protected $userRepository;

	/**
	 * @var CrowdClient
	 */
	protected $crowdClient;

	/**
	 * @return void
	 */
	public function initializeObject() {
		$this->crowdClient = new CrowdClient($this->options['crowdApplicationName'], $this->options['crowdApplicationPassword']);
	}

	/**
	 * Returns the class names of the tokens this provider can authenticate.
	 *
	 * @return array
	 */
	public function getTokenClassNames() {
		return array('TYPO3\Flow\Security\Authentication\Token\UsernamePassword');
	}

	/**
	 * Authenticates against a crowd instance.
	 *
	 * @param \TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException
	 */
	public function authenticate(TokenInterface $authenticationToken) {
		if (!($authenticationToken instanceof UsernamePassword)) {
			throw new UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1217339845);
		}

		$credentials = $authenticationToken->getCredentials();

		if (!isset($credentials['username']) || !isset($credentials['password'])) {
			if ($authenticationToken->getAuthenticationStatus() !== TokenInterface::AUTHENTICATION_SUCCESSFUL) {
				$authenticationToken->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
			}
			return;
		}

		$crowdAuthenticationResponse = $this->crowdClient->authenticate($credentials['username'], $credentials['password']);
		if ($crowdAuthenticationResponse === NULL) {
			$authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);
			return;
		}

		/** @var $account \TYPO3\Flow\Security\Account */
		$account = NULL;
		$providerName = $this->name;
		$accountRepository = $this->accountRepository;
		$this->securityContext->withoutAuthorizationChecks(function() use ($credentials, $providerName, $accountRepository, &$account) {
			$account = $accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName($credentials['username'], $providerName);
		});

		if ($account === NULL) {
			$account = new Account();
			$account->setAuthenticationProviderName($providerName);
			$account->setAccountIdentifier($credentials['username']);
			$this->accountRepository->add($account);
		}

		$authenticateRole = $this->policyService->getRole($this->options['authenticateRole']);
		if ($account->hasRole($authenticateRole) === FALSE) {
			$account->addRole($authenticateRole);
		}

		$crowdUser = $this->userRepository->findOneByAccount($account);
		if ($crowdUser !== NULL) {
			$crowdUser->sync($crowdAuthenticationResponse);
			$this->userRepository->update($crowdUser);
		} else {
			$crowdUser = new User($account, $crowdAuthenticationResponse);
			$this->userRepository->add($crowdUser);
		}

		$authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
		$authenticationToken->setAccount($account);
	}

}
