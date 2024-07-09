<?php

namespace Neos\IdNeosIo\Security\Authentication;

use Flownative\DoubleOptIn\Helper;
use Flownative\DoubleOptIn\UnknownPresetException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\Provider\AbstractProvider;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Exception\InvalidAuthenticationStatusException;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Exception\UnsupportedAuthenticationTokenException;
use Neos\Flow\Security\Policy\PolicyService;

/**
 * Authentication that can authenticate Flownative.DoubleOptIn tokens
 */
class TokenProvider extends AbstractProvider
{

    /**
     * @Flow\Inject
     * @var Helper
     */
    protected $doubleOptInHelper;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    public function getTokenClassNames(): array
    {
        return [TokenHashToken::class];
    }

    /**
     * @param TokenInterface $authenticationToken
     * @throws UnsupportedAuthenticationTokenException | InvalidAuthenticationStatusException | UnknownPresetException | NoSuchRoleException | InvalidTokenException
     */
    public function authenticate(TokenInterface $authenticationToken): void
    {
        if (!($authenticationToken instanceof TokenHashToken)) {
            throw new UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1533736501);
        }
        $credentials = $authenticationToken->getCredentials();

        if (!isset($credentials['tokenHash'])) {
            $authenticationToken->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
            return;
        }

        $token = $this->doubleOptInHelper->validateTokenHash($credentials['tokenHash']);
        if ($token === null) {
            throw new InvalidTokenException(sprintf('Invalid token hash "%s"', $credentials['tokenHash']), 1533818080);
        }

        $account = new Account();
        $account->setAuthenticationProviderName($this->name);
        $account->setAccountIdentifier($token->getIdentifier());
        $authenticateRole = $this->policyService->getRole($this->options['authenticateRole']);
        $account->addRole($authenticateRole);

        $authenticationToken->setAccount($account);
        $authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
    }
}
