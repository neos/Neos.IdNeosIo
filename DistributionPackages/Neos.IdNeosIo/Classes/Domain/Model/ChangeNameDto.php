<?php

namespace Neos\IdNeosIo\Domain\Model;

use Neos\Flow\Annotations as Flow;

class ChangeNameDto
{

    /**
     * @Flow\Validate(type="NotEmpty")
     * @var string
     */
    protected $firstName;

    /**
     * @Flow\Validate(type="NotEmpty")
     * @var string
     */
    protected $lastName;

    public function __construct(string $firstName, string $lastName)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }


    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }
}
