<?php

namespace Neos\DiscourseCrowdSso\Command;

use Neos\DiscourseCrowdSso\DiscourseService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

class DiscourseCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var DiscourseService
     */
    protected $discourseService;

    /**
     * A simple command to test basic access to the Discourse API
     */
    public function testAccessCommand(): void
    {
        $this->discourseService->hasUserWithEmail('hello@neos.io');
        $this->outputLine('<success>Success!</success>');
    }
}
