<?php

namespace Drupal\composerize\Commands;

use Drupal\composerize\Generator;
use Drush\Commands\DrushCommands;

class ComposerizeCommands extends DrushCommands {

  /**
   * The composer.json generator service.
   *
   * @var \Drupal\composerize\Generator
   */
  protected $generator;

  /**
   * ComposerizeCommands constructor.
   *
   * @param \Drupal\composerize\Generator $generator
   *   The composer.json generator service.
   */
  public function __construct(Generator $generator) {
    $this->generator = $generator;
  }

  /**
   * Generates a composer.json from the currently installed code base.
   *
   * @command generate:composer
   *
   * @usage generate:composer
   */
  public function generateComposer() {
    $this->io()->write($this->generator->generate());
  }

}
