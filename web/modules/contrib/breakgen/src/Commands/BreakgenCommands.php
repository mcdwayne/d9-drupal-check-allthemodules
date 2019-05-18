<?php

namespace Drupal\breakgen\Commands;

use Drush\Commands\DrushCommands;
use Drupal\breakgen\Service\ImageStyleGenerator;

/**
 * Class BreakgenCommands
 * @package Drupal\breakgen\Commands
 */
class BreakgenCommands extends DrushCommands
{
    protected $generator;

    /**
     * BreakgenCommands constructor.
     * @param \Drupal\breakgen\Service\ImageStyleGenerator $generator
     */
    public function __construct(ImageStyleGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * This command generates breakgen breakpoints for Drupal 8 out of the theme file
     *
     * @command breakgen:generate
     * @validate-module-enabled breakgen
     * @aliases bg:generate, bg, breakgen-generate
     * @param string|null $theme
     */
    public function generate($theme = null)
    {
        $this->generator->generate($theme);
    }

    /**
     * This command clears breakgen breakpoints for Drupal 8 out of the theme file.
     *
     * @command breakgen:clean
     * @validate-module-enabled breakgen
     * @aliases bg:clean, bc, breakgen-clean
     */
    public function clear()
    {
        $this->generator->clear();
    }
}
