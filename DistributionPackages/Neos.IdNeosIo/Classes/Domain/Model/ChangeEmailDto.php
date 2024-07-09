<?php

namespace Neos\IdNeosIo\Domain\Model;

use Neos\Flow\Annotations as Flow;

class ChangeEmailDto
{
    /**
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="EmailAddress")
     */
    protected string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
