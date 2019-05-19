<?php

namespace Drupal\smart_content\Plugin\smart_content\DecisionAgent;

use Drupal\smart_content\Annotation\SmartDecisionAgent;
use Drupal\smart_content\DecisionAgent\DecisionAgentBase;

/**
 * Provides a default Smart Condition.
 *
 * @SmartDecisionAgent(
 *   id = "server",
 *   label = @Translation("Server Side"),
 *   placeholder_attribute = "data-smart-content-server",
 *   description = @Translation("Hides conditions, useful for conditions with
 *   private values, reduces performance."),
 * )
 */
class Server extends DecisionAgentBase {

  /**
   * Returns required JS libraries for this type.
   *
   * @return mixed
   */
  public function getLibraries() {
    return ['smart_content/decision.server'];
  }

  public function getAttachedSettings() {

  }

  public function isProcessedClientSide() {
    return FALSE;
  }
}