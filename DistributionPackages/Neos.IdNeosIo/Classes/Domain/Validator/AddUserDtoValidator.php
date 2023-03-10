<?php
namespace Neos\IdNeosIo\Domain\Validator;

use Neos\CrowdClient\Domain\Service\CrowdClient;
use Neos\DiscourseCrowdSso\DiscourseService;
use Neos\Error\Messages\Error;
use Neos\Flow\Validation\Validator\AbstractValidator;
use Neos\Flow\Annotations as Flow;
use Neos\IdNeosIo\Domain\Model\AddUserDto;

class AddUserDtoValidator extends AbstractValidator
{

    /**
     * @Flow\Inject
     * @var CrowdClient
     */
    protected $crowdClient;

    /**
     * @Flow\Inject
     * @var DiscourseService
     */
    protected $discourseService;

    protected function isValid($value): void
    {
        if (!$value instanceof AddUserDto) {
            $this->addError('Value must be of type AddUserDto', 1436254418);
        }

        if (!empty($value->getEmail()) && $this->discourseService->hasUserWithEmail($value->getEmail())) {
            $this->pushResult()->forProperty('email')->addError(new Error('This email address is already used on discuss.neos.io', 1536227703));
        }

        if ($value->getPassword() !== $value->getPasswordConfirmation()) {
            $this->pushResult()->forProperty('passwordConfirmation')->addError(new Error('Password does not match confirmation', 1436254655));
        }

        if ($this->crowdClient->getUser($value->getUsername()) !== null) {
            $this->pushResult()->forProperty('username')->addError(new Error('The chosen username is not available', 1436267801));
        }
    }

}
