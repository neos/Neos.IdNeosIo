<?php
declare(strict_types=1);

namespace Neos\IdNeosIo\Domain\Validator;

use Neos\DiscourseCrowdSso\DiscourseService;
use Neos\Error\Messages\Error;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Validation\Validator\AbstractValidator;
use Neos\IdNeosIo\Domain\Model\ChangeEmailDto;

class ChangeEmailDtoValidator extends AbstractValidator
{
    /**
     * @Flow\Inject
     * @var DiscourseService
     */
    protected $discourseService;

    /**
     * @param mixed $value
     */
    protected function isValid($value): void
    {
        if (!$value instanceof ChangeEmailDto) {
            $this->addError('Value must be of type ChangeEmailDto', 1536227679);
        }

        if (!empty($value->getEmail()) && $this->discourseService->hasUserWithEmail($value->getEmail())) {
            $this->pushResult()->forProperty('email')->addError(new Error('This email address is already used on discuss.neos.io', 1536227696));
        }
    }

}
