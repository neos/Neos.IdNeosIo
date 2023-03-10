<?php
namespace Neos\IdNeosIo\Domain\Validator;

use Neos\Error\Messages\Error;
use Neos\Flow\Validation\Validator\AbstractValidator;
use Neos\IdNeosIo\Domain\Model\ResetPasswordDto;

class ResetPasswordDtoValidator extends AbstractValidator
{

    protected function isValid($value): void
    {
        if (!$value instanceof ResetPasswordDto) {
            $this->addError('Value must be of type ResetPasswordDto', 1533811010);
        }

        if ($value->getPassword() !== $value->getPasswordConfirmation()) {
            $this->pushResult()->forProperty('passwordConfirmation')->addError(new Error('Password does not match confirmation', 1533810994));
        }
    }

}
