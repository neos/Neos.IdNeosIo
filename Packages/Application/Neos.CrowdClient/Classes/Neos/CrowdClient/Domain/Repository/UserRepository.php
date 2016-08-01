<?php
namespace Neos\CrowdClient\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Neos.CrowdClient".      *
 *                                                                        *
 *                                                                        */

use Neos\CrowdClient\Domain\Model\User;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Flow\Security\Account;

/**
 * @Flow\Scope("singleton")
 */
class UserRepository extends Repository {

	/**
	 * @param Account $account
	 * @return User
	 */
	public function findOneByAccount(Account $account) {
		$query = $this->createQuery();
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $query->matching(
				$query->equals('account', $account)
			)
			->execute()
			->getFirst();
	}
}