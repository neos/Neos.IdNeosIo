<?php

namespace Neos\DiscourseCrowdSso;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Account;

/**
 * @Flow\Proxy(false)
 */
final class SsoPayload implements \JsonSerializable
{
    /**
     * @see https://meta.discourse.org/t/official-single-sign-on-for-discourse-sso/13045
     */
    private const ALLOWED_CUSTOM_FIELDS = ['email', 'name', 'avatar_url', 'avatar_force_update', 'bio', 'admin', 'moderator', 'suppress_welcome_message'];

    /**
     * @var array
     */
    private $payload;

    private function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public static function fromAccount(Account $account, array $customFields = []): self
    {
        $invalidCustomFields = array_diff_key($customFields, array_flip(self::ALLOWED_CUSTOM_FIELDS));
        if ($invalidCustomFields !== []) {
            throw new \InvalidArgumentException(sprintf('Invalid custom payload field(s) "%s"', implode('", "', array_keys($invalidCustomFields))), 1534427787);
        }
        $payload = $customFields;
        $payload['username'] = $account->getAccountIdentifier();
        $payload['external_id'] = $account->getAccountIdentifier();
        return new static($payload);
    }

    public function withNonce(string $nonce): self
    {
        $payload = $this->payload;
        $payload['nonce'] = $nonce;
        return new static($payload);
    }

    public function hasExternalId(): bool
    {
        return $this->has('external_id');
    }

    public function hasNonce(): bool
    {
        return $this->has('nonce');
    }

    private function has(string $fieldName): bool
    {
        return isset($this->payload[$fieldName]) && !empty($this->payload[$fieldName]);
    }

    public function jsonSerialize(): array
    {
        return $this->payload;
    }

    public function toArray(): array
    {
        return $this->payload;
    }
}
