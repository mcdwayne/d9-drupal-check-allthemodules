<?php
/**
 * @file
 * Contains \Drupal\mailmute\Plugin\mailmute\SendState\SendStateBase.
 */

namespace Drupal\mailmute\Plugin\mailmute\SendState;

use Drupal\Core\Plugin\PluginBase;

/**
 * A send state determines whether messages to an address should be suppressed.
 *
 * This provides dumb implementations for all methods.
 *
 * @ingroup plugin
 */
abstract class SendStateBase extends PluginBase implements SendStateInterface {

  /**
   * {@inheritdoc}
   */
  public function display() {
    // @todo Show if muting or not (or change the labels in all definitions): https://www.drupal.org/node/2381775
    return array(
      '#markup' => $this->getPluginDefinition()['label'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form() {
    return array();
  }

  /**
   * Tells whether to suppress messages to addresses with this state.
   *
   * @return bool
   *   TRUE if messages should be suppressed, FALSE if they should be sent.
   */
  public function isMute() {
    return $this->getPluginDefinition()['mute'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
