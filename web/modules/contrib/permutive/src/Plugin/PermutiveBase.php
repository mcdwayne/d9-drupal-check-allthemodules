<?php

namespace Drupal\permutive\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Permutive plugins.
 */
abstract class PermutiveBase extends PluginBase implements PermutiveInterface {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getPluginDefinition()['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getClientType() {
    return $this->getPluginDefinition()['clientType'];
  }

}
