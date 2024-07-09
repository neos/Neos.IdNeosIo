<?php

namespace Neos\IdNeosIo;

use Neos\CrowdClient\Domain\Dto\User;
use Neos\CrowdClient\Domain\Service\CrowdClient;
use Neos\DiscourseCrowdSso\DiscourseService;
use Neos\DiscourseCrowdSso\SsoPayload;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Security\Context as SecurityContext;
use Psr\Log\LoggerInterface;

/**
 * The Neos.IdNeosIo Package
 */
class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap
     */
    public function boot(Bootstrap $bootstrap): void
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        # Synchronize Crowd User Profile changes to discourse
        $dispatcher->connect(
            CrowdClient::class, 'userUpdated',
            function (User $crowdUser, array $newValues) use ($bootstrap) {
                $objectManager = $bootstrap->getObjectManager();

                /** @var LoggerInterface $logger */
                $logger = $objectManager->get(LoggerInterface::class);

                /** @var SecurityContext $securityContext */
                $securityContext = $objectManager->get(SecurityContext::class);
                $account = $securityContext->getAccount();
                if ($account === null) {
                    $logger->error('Could not synchronize user to discourse', ['reason' => 'No account in Security Context']);
                    return;
                }
                /** @var DiscourseService $discourseService */
                $discourseService = $objectManager->get(DiscourseService::class);
                $customFields = [
                    'name' => isset($newValues['first-name'], $newValues['last-name']) ? $newValues['first-name'] . ' ' . $newValues['last-name'] : $crowdUser->getFullName(),
                    'email' => $newValues['email'] ?? $crowdUser->getEmail(),
                ];
                try {
                    $discourseService->synchronizeUser(SsoPayload::fromAccount($account, $customFields));
                } catch (\RuntimeException $exception) {
                    $exceptionMessage = sprintf('%s (%d)', $exception->getMessage(), $exception->getCode());
                    if ($exception->getPrevious() !== null) {
                        $exceptionMessage .= sprintf(' -> %s (%d)', $exception->getPrevious()->getMessage(), $exception->getPrevious()->getCode());
                    }
                    $logger->error('Could not synchronize user to discourse', ['exception' => $exceptionMessage]);
                }
            }
        );
    }

}
