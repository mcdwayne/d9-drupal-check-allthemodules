<?php

namespace Drupal\inmail_mailmute\Plugin\mailmute\SendState;

/**
 * Indicates that soft bounces have been received from the address.
 *
 * @ingroup mailmute
 *
 * @SendState(
 *   id = "inmail_counting",
 *   label = @Translation("Counting soft bounces"),
 *   description = @Translation("Earlier messages to the address have resulted in soft bounces."),
 *   mute = false,
 *   admin = true,
 *   parent_id = "send"
 * )
 */
class CountingBounces extends BounceSendstateBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function display() {
    $display = parent::display();

    $display['label'] = array(
      '#markup' => $this->t($this->getThreshold() ? '@label (@count of @threshold received)' : '@label (@count received, no threshold set)', array(
        '@label' => $this->getPluginDefinition()['label'],
        '@count' => $this->getUnprocessedCount(),
        '@threshold' => $this->getThreshold(),
      )),
    );

    return $display;
  }

  /**
   * Returns the current number of received bounces.
   *
   * @return int
   *   The number of received bounces.
   */
  public function getUnprocessedCount() {
    return isset($this->configuration['count']) ? $this->configuration['count'] : 0;
  }

  /**
   * Set the current count of received bounces.
   *
   * @param int $count
   *   The new number of bounces.
   */
  public function setCount($count) {
    $this->configuration['count'] = $count;
  }

  /**
   * Increment the current count of received bounces by 1.
   */
  public function increment() {
    $this->setCount($this->getUnprocessedCount() + 1);
  }

  /**
   * Returns the accepted number of bounces before address should be muted.
   *
   * @return int
   *   An integer threshold value.
   */
  public function getThreshold() {
    return isset($this->configuration['threshold']) ? $this->configuration['threshold'] : NULL;
  }

  /**
   * Set the accepted number of bounces.
   *
   * @param int $threshold
   *   The accepted number of bounces.
   *
   * @throws \InvalidArgumentException
   *   If $threshold is not a positive integer.
   */
  public function setThreshold($threshold) {
    if (intval($threshold) <= 0) {
      throw new \InvalidArgumentException('Threshold must be a positive integer.');
    }
    $this->configuration['threshold'] = intval($threshold);
  }

}
