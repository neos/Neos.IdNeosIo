<?php
namespace Neos\CrowdClient\Security\Authentication\Provider;

/*                                                                         *
 * This script belongs to the TYPO3 Flow package "Neos.DiscourseCrowdSso". *
 *                                                                         *
 *                                                                         */

use Neos\CrowdClient\Domain\Service\CrowdClient;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authentication\Provider\AbstractProvider;
use TYPO3\Flow\Security\Authentication\Token\Typo3OrgSsoToken;
use TYPO3\Flow\Security\Authentication\Token\UsernamePassword;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Party\Domain\Model\ElectronicAddress;
use TYPO3\Party\Domain\Model\Person;
use TYPO3\Party\Domain\Model\PersonName;
use TYPO3\Party\Domain\Repository\PartyRepository;
use TYPO3\Party\Domain\Service\PartyService;

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
	 * @var PartyRepository
	 */
	protected $partyRepository;

	/**
	 * @var PartyService
	 * @Flow\Inject
	 */
	protected $partyService;

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

		if (is_array($credentials) && isset($credentials['username']) && isset($credentials['password'])) {

			$crowdAuthenticationResponse = $this->crowdClient->authenticate($credentials['username'], $credentials['password']);

			if ($crowdAuthenticationResponse !== NULL) {

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

				$crowdUser = $this->partyService->getAssignedPartyOfAccount($account);

				if ($crowdUser instanceof Person) {
					if ($crowdUser->getName()->getFirstName() !== $crowdAuthenticationResponse['first-name']) {
						$crowdUser->getName()->setFirstName($crowdAuthenticationResponse['first-name']);
						$this->partyRepository->update($crowdUser);
					}
					if ($crowdUser->getName()->getLastName() !== $crowdAuthenticationResponse['last-name']) {
						$crowdUser->getName()->setLastName($crowdAuthenticationResponse['last-name']);
						$this->partyRepository->update($crowdUser);
					}
					if ($crowdUser->getPrimaryElectronicAddress()->getIdentifier() !== $crowdAuthenticationResponse['email']) {
						$crowdUser->getPrimaryElectronicAddress()->setIdentifier($crowdAuthenticationResponse['email']);
						$this->partyRepository->update($crowdUser);
					}
				} else {
					$crowdUser = new Person();
					$crowdUser->setName(new PersonName('', $crowdAuthenticationResponse['first-name'], '', $crowdAuthenticationResponse['last-name']));
					$email = new ElectronicAddress();
					$email->setIdentifier($crowdAuthenticationResponse['email']);
					$email->setType(ElectronicAddress::TYPE_EMAIL);
					$crowdUser->setPrimaryElectronicAddress($email);

					$this->partyRepository->add($crowdUser);
					$this->partyService->assignAccountToParty($account, $crowdUser);
				}

				$authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
				$authenticationToken->setAccount($account);
			} else {
				$authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);
			}
		} elseif ($authenticationToken->getAuthenticationStatus() !== TokenInterface::AUTHENTICATION_SUCCESSFUL) {
			$authenticationToken->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
		}
	}

}
