<?php

namespace Drupal\smart_content\Plugin\smart_content\DecisionAgent;

use Drupal\smart_content\Annotation\SmartDecisionAgent;
use Drupal\smart_content\DecisionAgent\DecisionAgentBase;

/**
 * Provides a default Smart Condition.
 *
 * @SmartDecisionAgent(
 *   id = "client",
 *   label = @Translation("Client Side"),
 *   placeholder_attribute = "data-smart-content-client",
 *   description = @Translation("Hides conditions, useful for conditions with
 *   private values, reduces performance."),
 * )
 */
class Client extends DecisionAgentBase {

  /**
   * Returns required JS libraries for this type.
   *
   * @return mixed
   */
  public function getLibraries() {
    return ['smart_content/decision.client'];
    // TODO: Implement getAttached() method.
  }

  public function getAttachedSettings() {

  }

  function isProcessedClientSide() {
    return TRUE;
  }
}