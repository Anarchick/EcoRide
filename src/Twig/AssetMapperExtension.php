<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * AssetMapper has conflict in test environment.
 * This extension override the importmap function ONLY in test environment.
 */
class AssetMapperExtension extends AbstractExtension
{
    private string $environment;

    public function __construct(string $environment)
    {
        $this->environment = $environment;
    }

    public function getFunctions(): array
    {
        // Only override importmap function in test environment
        if ($this->environment === 'test') {
            return [
                new TwigFunction('importmap', [$this, 'renderImportmap'], ['is_safe' => ['html']]),
            ];
        }

        return [];
    }

    public function renderImportmap($entrypoint = 'app'): string
    {
        // Returns an empty string in the test environment
        // Accept either string or array of entrypoints for compatibility
        return '';
    }
}
