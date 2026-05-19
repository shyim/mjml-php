<?php

declare(strict_types=1);

namespace Mjml;

use Mjml\Validation\ValidationLevel;

final readonly class MjmlOptions
{
    /**
     * @param array<string, string> $fonts Font name => URL map
     * @param list<string>|null $includePath Additional root directories allowed for mj-include
     */
    public function __construct(
        public ValidationLevel $validationLevel = ValidationLevel::Strict,
        public bool $minify = false,
        public bool $beautify = false,
        public bool $keepComments = true,
        public string $language = 'und',
        public string $dir = 'auto',
        public array $fonts = [
            'Open Sans' => 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,700',
            'Droid Sans' => 'https://fonts.googleapis.com/css?family=Droid+Sans:300,400,500,700',
            'Lato' => 'https://fonts.googleapis.com/css?family=Lato:300,400,500,700',
            'Roboto' => 'https://fonts.googleapis.com/css?family=Roboto:300,400,500,700',
            'Ubuntu' => 'https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700',
        ],
        public ?string $filePath = null,
        public bool $ignoreIncludes = true,
        public ?array $includePath = null,
    ) {}
}
