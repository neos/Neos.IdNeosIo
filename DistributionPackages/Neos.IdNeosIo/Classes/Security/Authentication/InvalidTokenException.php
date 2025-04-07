<?php
declare(strict_types=1);

namespace Neos\IdNeosIo\Security\Authentication;

use Neos\Flow\Security\Exception\AccessDeniedException;

class InvalidTokenException extends AccessDeniedException
{
}
