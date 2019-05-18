<?php

namespace Drupal\chatbot_api\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Intent Plugin plugins.
 */
interface IntentPluginInterface extends PluginInspectionInterface {

  /**
   * Process the request.
   *
   * This method should contain the logic processing the request and producing
   * a response.
   */
  public function process();

}
