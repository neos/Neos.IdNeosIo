<?php
declare(strict_types=1);

namespace Neos\IdNeosIo\Domain\Model;

use Neos\Flow\Annotations as Flow;

class AddUserDto
{
    /**
     * @Flow\Validate(type="NotEmpty")
     */
    protected string $firstName;

    /**
     * @Flow\Validate(type="NotEmpty")
     */
    protected string $lastName;

    /**
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="EmailAddress")
     */
    protected string $email;

    /**
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="StringLength", options={"minimum"=4})
     */
    protected string $username;

    /**
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="StringLength", options={"minimum"=8})
     */
    protected string $password;

    protected string $passwordConfirmation;

    public function __construct(string $firstName, string $lastName, string $email, string $username, string $password, string $passwordConfirmation)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->username;
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
