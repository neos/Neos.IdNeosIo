<?php
namespace Neos\CrowdClient\Domain\Dto;

use Neos\Flow\Annotations as Flow;

/**
 * DTO representing a Crowd User
 *
 * @Flow\Proxy(false)
 */
final class User
{

    /**
     * This refers to the "username"
     *
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $email;

    private function __construct(string $name, string $fistName, string $lastName, string $email)
    {
        $this->name = $name;
        $this->firstName = $fistName;
        $this->lastName = $lastName;
        $this->email = $email;
    }

    public static function fromCrowdResponse(string $crowdResponse): ?self
    {
        $userData = json_decode($crowdResponse, true);
        if (!is_array($userData)) {
            return null;
        }
        return new static($userData['name'], $userData['first-name'], $userData['last-name'], $userData['email']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'first-name' => $this->firstName,
            'last-name' => $this->lastName,
            'email' => $this->email,
        ];
    }
}
