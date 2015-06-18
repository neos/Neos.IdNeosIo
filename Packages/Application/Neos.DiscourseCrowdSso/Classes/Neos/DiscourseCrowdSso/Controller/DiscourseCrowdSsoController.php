<?php
namespace Neos\DiscourseCrowdSso\Controller;

/*                                                                         *
 * This script belongs to the TYPO3 Flow package "Neos.DiscourseCrowdSso". *
 *                                                                         *
 *                                                                         */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Cryptography\Algorithms;
use TYPO3\Party\Domain\Model\Person;
use TYPO3\Party\Domain\Service\PartyService;

class DiscourseCrowdSsoController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var PartyService
	 * @Flow\Inject
	 */
	protected $partyService;

	/**
	 * @var string
	 * @Flow\Inject(setting="ssoSecret")
	 */
	protected $ssoSecret;

	/**
	 * @var string
	 * @Flow\Inject(setting="discourseSsoUrl")
	 */
	protected $discourseSsoUrl;

	/**
	 * @return void
	 */
	public function loginAction() {
	}

	/**
	 * @param string $sso
	 * @param string $sig
	 * @return void
	 * @Flow\SkipCsrfProtection
	 */
	public function authenticateDiscourseUserAction($sso = '', $sig = '') {

		if ($sso === '' && $sig === '') {
			$argumentsOfInterceptedRequest = $this->securityContext->getInterceptedRequest()->getArguments();
			if (!isset($argumentsOfInterceptedRequest['sso']) || !isset($argumentsOfInterceptedRequest['sig'])) {
				return 'This page needs to be called with valid sso and sig arguments from crowd!';
			}
			$sso = $argumentsOfInterceptedRequest['sso'];
			$sig = $argumentsOfInterceptedRequest['sig'];
		}

		if (hash_hmac('sha256', $sso, $this->ssoSecret) === $sig) {

			parse_str(base64_decode($sso), $incomingPayload);

			$currentAccount = $this->securityContext->getAccount();
			/** @var Person $crowdUser */
			$crowdUser = $this->partyService->getAssignedPartyOfAccount($currentAccount);

			$outgoingPayload = base64_encode(http_build_query(array(
					'nonce' => $incomingPayload['nonce'],
					'email' => $crowdUser->getPrimaryElectronicAddress()->getIdentifier(),
					'name' => $crowdUser->getName()->getFullName(),
					'username' => $currentAccount->getAccountIdentifier(),
					'external_id' => $currentAccount->getAccountIdentifier()
				), '', '&', PHP_QUERY_RFC3986));

			$outgoingSignature = hash_hmac('sha256', $outgoingPayload, $this->ssoSecret);

			$this->redirectToUri(sprintf('%s?%s', $this->discourseSsoUrl,
					http_build_query(array(
						'sso' => $outgoingPayload,
						'sig' => $outgoingSignature
						), '', '&', PHP_QUERY_RFC3986)
				), 0, 302);
		}

		return 'Sorry, we couldn\'t log you in';
	}
}