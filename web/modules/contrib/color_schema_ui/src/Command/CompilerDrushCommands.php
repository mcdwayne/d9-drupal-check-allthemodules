<?php

namespace Drupal\color_schema_ui\Command;

use Drupal\color_schema_ui\SCSSCompilerFacade;
use Drush\Commands\DrushCommands;

/**
 * Defines drush commands to manage the demo content.
 */
class CompilerDrushCommands extends DrushCommands {

  /**
   * @var SCSSCompilerFacade
   */
  private $compiler;

  public function __construct(SCSSCompilerFacade $compiler) {
    parent::__construct();
    $this->compiler = $compiler;
  }

  /**
   * Deletes and regenerates the demo content.
   *
   * @command color_schema_ui:compile
   * @aliases csuc
   */
  public function compile() {
    $this->compiler->compileSCSSToFilesystem();
  }

}
