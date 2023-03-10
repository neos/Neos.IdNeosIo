<?php
namespace Neos\CrowdClient\Security\Authentication\Provider;

use Neos\CrowdClient\Domain\Service\CrowdClient;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\Provider\AbstractProvider;
use Neos\Flow\Security\Authentication\Token\UsernamePassword;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Exception\InvalidAuthenticationStatusException;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Exception\UnsupportedAuthenticationTokenException;
use Neos\Flow\Security\Policy\PolicyService;

/**
 * An authentication provider that authenticates a username and password against
 * an atlassian crowd instance.
 */
class CrowdProvider extends AbstractProvider
{

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @var CrowdClient
     */
    protected $crowdClient;

    public function initializeObject(): void
    {
        $this->crowdClient = new CrowdClient($this->options['crowdApplicationName'], $this->options['crowdApplicationPassword']);
    }

    public function getTokenClassNames(): array
    {
        return [UsernamePassword::class];
    }

    /**
     * Authenticates against a crowd instance.
     *
     * @param TokenInterface $authenticationToken The token to be authenticated
     * @return void
     * @throws UnsupportedAuthenticationTokenException
     * @throws InvalidAuthenticationStatusException
     * @throws NoSuchRoleException
     */
    public function authenticate(TokenInterface $authenticationToken): void
    {
        if (!($authenticationToken instanceof UsernamePassword)) {
            throw new UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1217339845);
        }

        $credentials = $authenticationToken->getCredentials();

        if (!isset($credentials['username']) || !isset($credentials['password'])) {
            $authenticationToken->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
            return;
        }

        $crowdUser = $this->crowdClient->authenticate($credentials['username'], $credentials['password']);
        if ($crowdUser === null) {
            $authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);
            return;
        }

        $account = new Account();
        $account->setAuthenticationProviderName($this->name);
        $account->setAccountIdentifier($crowdUser->getName());
        $authenticateRole = $this->policyService->getRole($this->options['authenticateRole']);
        $account->addRole($authenticateRole);

        $authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
        $authenticationToken->setAccount($account);
    }

}
