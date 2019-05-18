<?php

namespace Drupal\nimbus\Commands;

use Drupal\nimbus\Controller\OverrideUuidController;
use Drush\Commands\DrushCommands;

/**
 * Class OverrideUuidCommand.
 *
 * @package Drupal\nimbus\Controller
 */
class OverrideUuidCommands extends DrushCommands {

  /**
   * @var \Drupal\nimbus\Controller\NimbusImportController
   */
  private $controller;

  /**
   *
   */
  public function __construct(OverrideUuidController $controller) {
    $this->controller = $controller;
  }

  /**
   * Override the uuid.
   *
   * @command nimbus:force-uuid
   * @aliases fuuid
   */
  public function overrideUuid($label = NULL, $options = []) {
    $this->controller->uuidUpdateCommand($this->input(), $this->output());
  }

}
