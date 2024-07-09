<?php
declare(strict_types=1);

namespace Neos\IdNeosIo\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class ResetPasswordDto
{
    /**
     * @Flow\Validate(type="StringLength", options={"minimum"=8})
     */
    protected string $password;

    protected string $passwordConfirmation;

    public function __construct(string $password, string $passwordConfirmation)
    {
        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPasswordConfirmation(): string
    {
        return $this->passwordConfirmation;
    }
}
