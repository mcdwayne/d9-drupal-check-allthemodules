<?php

namespace Drupal\shutdown\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\shutdown\ShutdownCore;

/**
 * Provides a 'Open the website' action.
 *
 * @RulesAction(
 *   id = "shutdown_action_open_website",
 *   label = @Translation("Open the website"),
 *   category = @Translation("System")
 * )
 */
class OpenWebsite extends RulesActionBase {

  /**
   * Opens the website.
   */
  protected function doExecute() {
    $shutdown = new ShutdownCore();
    $shutdown->openWebsite();
  }

}
