<?php

namespace Drupal\nimbus\Commands;

use Drupal\nimbus\Controller\NimbusExportController;
use Drush\Commands\DrushCommands;

/**
 *
 */
class NimbusConfigExportCommands extends DrushCommands {
  /**
   * @var \Drupal\nimbus\Controller\NimbusExportController
   */
  private $controller;

  /**
   *
   */
  public function __construct(NimbusExportController $controller) {
    $this->controller = $controller;
  }

  /**
   * Export Drupal configuration to a directory.
   *
   * @command config:export
   * @interact-config-label
   * @param string $label
   *   A config directory label (i.e. a key in $config_directories array in settings.php).
   *
   * @option add Run `git add -p` after exporting. This lets you choose which config changes to sync for commit.
   * @option commit Run `git add -A` and `git commit` after exporting.  This commits everything that was exported without prompting.
   * @option message Commit comment for the exported configuration.  Optional; may only be used with --commit.
   * @option destination An arbitrary directory that should receive the exported files. A backup directory is used when no value is provided.
   * @option diff Show preview as a diff, instead of a change list.
   * @usage drush config:export --destination
   *   Export configuration; Save files in a backup directory named config-export.
   * @aliases cex,config-export
   */
  public function export($label = NULL, $options = ['add' => FALSE, 'commit' => FALSE, 'message' => self::REQ, 'destination' => self::OPT, 'diff' => FALSE]) {
    $this->controller->configurationExport($this->input(), $this->output());
  }

}
