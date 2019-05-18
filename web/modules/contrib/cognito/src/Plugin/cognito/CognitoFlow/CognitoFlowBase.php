<?php

namespace Drupal\cognito\Plugin\cognito\CognitoFlow;

use Drupal\cognito\Plugin\cognito\CognitoFlowInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginWithFormsTrait;

/**
 * Base class for our flow plugins.
 */
abstract class CognitoFlowBase extends PluginBase implements CognitoFlowInterface {

  use PluginWithFormsTrait;

  /**
   * {@inheritdoc}
   */
  public function getChallengeRoute($challengeName) {
    if (isset($this->getPluginDefinition()['challenges'][$challengeName]['route'])) {
      return $this->getPluginDefinition()['challenges'][$challengeName]['route'];
    }
  }

}
