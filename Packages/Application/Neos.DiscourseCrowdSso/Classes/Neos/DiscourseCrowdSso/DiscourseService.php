<?php
namespace Neos\DiscourseCrowdSso;

use GuzzleHttp\Exception\RequestException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Uri;
use GuzzleHttp\Client as HttpClient;
use Neos\Flow\Security\Account;

/**
 * @Flow\Scope("singleton")
 */
final class DiscourseService
{

    /**
     * @var string
     */
    private $discourseBaseUri;

    /**
     * @var string
     */
    private $ssoSecret;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiUsername;

    /**
     * @var HttpClient
     */
    private $httpClient;

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
     *
     * @param string $sso incoming SSO payload
     * @param string $sig SSO signature
     * @param SsoPayload $payload outgoing SSO payload
     * @return Uri The URI to redirect the user to
     */
    public function authenticate(string $sso, string $sig, SsoPayload $payload): Uri
    {
        if ($sig !== hash_hmac('sha256', $sso, $this->ssoSecret)) {
            throw new \RuntimeException('Invalid SSO signature!', 1534422486);
        }
        parse_str(base64_decode($sso), $incomingPayload);
        if (!isset($incomingPayload['nonce']) || empty($incomingPayload['nonce'])) {
            throw new \RuntimeException('Missing SSO nonce!', 1534429668);
        }

        $outgoingPayload = $payload->withNonce($incomingPayload['nonce']);
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
     *
     * @param SsoPayload $payload
     */
    public function synchronizeUser(SsoPayload $payload): void
    {
        if (!$payload->hasExternalId()) {
            throw new \RuntimeException('Missing "external_id" in payload!', 1534436199);
        }
        $sso = base64_encode(http_build_query($payload->jsonSerialize()));
        $postParameters = [
            'sso' => $sso,
            'sig' => hash_hmac('sha256', $sso, $this->ssoSecret),
            'api_key' => $this->apiKey,
            'api_username' => $this->apiUsername,
        ];

        try {
            $this->httpClient->post('/admin/users/sync_sso', ['form_params' => $postParameters]);
        } catch (RequestException $exception) {
            throw new \RuntimeException('Could not sync User data to discourse', 1534436256, $exception);
        }
    }

    /**
     * Logs out the user associated with the given $account
     *
     * @param Account $account
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
        $userData = json_decode($userResponse->getBody()->getContents(), true);
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
     *
     * @param string $email
     * @return bool
     */
    public function hasUserWithEmail(string $email): bool
    {
        try {
            $response = $this->httpClient->get('/admin/users/list/all.json', ['query' => ['email' => $email, 'api_key' => $this->apiKey, 'api_username' => $this->apiUsername]]);
        } catch (RequestException $exception) {
            throw new \RuntimeException('Could not look up email address with discourse', 1536231736, $exception);
        }
        $usersData = json_decode($response->getBody()->getContents(), true);
        return $usersData !== [];
    }

}
