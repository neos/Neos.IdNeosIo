<?php
declare(strict_types=1);

namespace Neos\DiscourseCrowdSso;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Account;

/**
 * @Flow\Scope("singleton")
 */
final class DiscourseService
{
    private string $discourseBaseUri;

    private string $ssoSecret;

    private string $apiKey;

    private string $apiUsername;

    private HttpClient $httpClient;

    public function __construct(string $discourseBaseUri, string $ssoSecret, string $apiKey, string $apiUsername)
    {
        $this->discourseBaseUri = $discourseBaseUri;
        $this->ssoSecret = $ssoSecret;
        $this->apiKey = $apiKey;
        $this->apiUsername = $apiUsername;

        $this->httpClient = new HttpClient([
            'base_uri' => rtrim($this->discourseBaseUri, '/') . '/',
            'headers' => [
                'User-Agent' => 'neos/discoursecrowdsso',
            ]
        ]);
    }

    /**
     * Authenticates a Discourse SSO request and returns the proper redirect URI
     * @see https://meta.discourse.org/t/official-single-sign-on-for-discourse-sso/13045
     */
    public function authenticate(string $incomingSsoPayload, string $ssoSignature, SsoPayload $outgoingSsoPayload): Uri
    {
        if ($ssoSignature !== hash_hmac('sha256', $incomingSsoPayload, $this->ssoSecret)) {
            throw new \RuntimeException('Invalid SSO signature!', 1534422486);
        }
        parse_str(base64_decode($incomingSsoPayload), $incomingPayload);
        if (empty($incomingPayload['nonce'])) {
            throw new \RuntimeException('Missing SSO nonce!', 1534429668);
        }

        $outgoingPayload = $outgoingSsoPayload->withNonce($incomingPayload['nonce']);
        $outgoingPayloadEncoded = base64_encode(http_build_query($outgoingPayload->jsonSerialize()));
        $queryParameters = [
            'sso' => $outgoingPayloadEncoded,
            'sig' => hash_hmac('sha256', $outgoingPayloadEncoded, $this->ssoSecret),
        ];
        return (new Uri($this->discourseBaseUri))
            ->withPath('/session/sso_login')
            ->withQuery(http_build_query($queryParameters));
    }

    /**
     * Synchronizes user data back to the discourse SSO endpoint
     */
    public function synchronizeUser(SsoPayload $payload): void
    {
        if (!$payload->hasExternalId()) {
            throw new \RuntimeException('Missing "external_id" in payload!', 1534436199);
        }
        $sso = base64_encode(http_build_query($payload->toArray()));
        $postParameters = [
            'sso' => $sso,
            'sig' => hash_hmac('sha256', $sso, $this->ssoSecret)
        ];

        $headers = [
            'Content-Type' => 'multipart/form-data',
            'Api-Key' => $this->apiKey,
            'Api-Username' => $this->apiUsername
        ];

        try {
            $this->httpClient->post('/admin/users/sync_sso', ['headers' => $headers, 'form_params' => $postParameters]);
        } catch (RequestException $exception) {
            throw new \RuntimeException('Could not sync User data to discourse', 1534436256, $exception);
        }
    }

    /**
     * Logs out the user associated with the given $account
     */
    public function logoutUser(Account $account): void
    {
        $credentialsQuery = http_build_query(['api_key' => $this->apiKey, 'api_username' => $this->apiUsername]);

        $userUri = sprintf('/users/%s.json?%s', $account->getAccountIdentifier(), $credentialsQuery);
        try {
            $userResponse = $this->httpClient->get($userUri);
        } catch (RequestException $exception) {
            throw new \RuntimeException('Could not fetch discourse User', 1534490588, $exception);
        }
        $userData = json_decode($userResponse->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        if (!isset($userData['user']['id'])) {
            throw new \RuntimeException('Missing user.id from /users/<username>.json response', 1534490654);
        }

        $logoutUri = sprintf('/admin/users/%s/log_out?%s', $userData['user']['id'], $credentialsQuery);
        try {
            $this->httpClient->post($logoutUri);
        } catch (RequestException $exception) {
            throw new \RuntimeException('Could not log out User', 1534489873, $exception);
        }
    }

    /**
     * Whether or not a user is registered with the given email
     *
     * This check is useful to prevent that a user signs up with an email address that is already in use
     * which would lead to an exception when trying to synchronize the user data
     */
    public function hasUserWithEmail(string $email): bool
    {
        try {
            $headers = [
                'Api-Key' => $this->apiKey,
                'Api-Username' => $this->apiUsername
            ];
            $response = $this->httpClient->get('/admin/users/list/all.json', ['headers' => $headers, 'query' => ['email' => $email]]);
        } catch (RequestException $exception) {
            throw new \RuntimeException('Could not look up email address with discourse', 1536231736, $exception);
        }
        $usersData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        return $usersData !== [];
    }

}
