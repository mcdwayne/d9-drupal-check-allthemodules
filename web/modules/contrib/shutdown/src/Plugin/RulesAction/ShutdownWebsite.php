<?php

namespace Drupal\shutdown\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\shutdown\ShutdownCore;

/**
 * Provides a 'Shut down the website' action.
 *
 * @RulesAction(
 *   id = "shutdown_action_shutdown_website",
 *   label = @Translation("Shut down the website"),
 *   category = @Translation("System")
 * )
 */
class ShutdownWebsite extends RulesActionBase {

  /**
   * Shuts down the website.
   */
  protected function doExecute() {
    $shutdown = new ShutdownCore();
    $shutdown->shutWebsite();
  }

}
