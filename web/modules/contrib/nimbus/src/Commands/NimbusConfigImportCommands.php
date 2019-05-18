<?php

namespace Drupal\nimbus\Commands;

use Drupal\nimbus\Controller\NimbusImportController;
use Drush\Commands\DrushCommands;

/**
 *
 */
class NimbusConfigImportCommands extends DrushCommands {
  /**
   * @var \Drupal\nimbus\Controller\NimbusImportController
   */
  private $controller;

  /**
   *
   */
  public function __construct(NimbusImportController $controller) {
    $this->controller = $controller;
  }

  /**
   * Import config from a config directory.
   *
   * @command config:import
   * @param $label
   *   A config directory label (i.e. a key in \$config_directories array in settings.php).
   *
   * @interact-config-label
   * @option diff Show preview as a diff.
   * @option preview Deprecated. Format for displaying proposed changes. Recognized values: list, diff.
   * @option source An arbitrary directory that holds the configuration files. An alternative to label argument
   * @option partial Allows for partial config imports from the source directory. Only updates and new configs will be processed with this flag (missing configs will not be deleted).
   * @aliases cim,config-import
   */
  public function import($label = NULL, $options = [
    'preview' => 'list',
    'source' => self::REQ,
    'partial' => FALSE,
    'diff' => FALSE,
  ]) {
    $this->controller->configurationImport($this->input(), $this->output());
  }

}
