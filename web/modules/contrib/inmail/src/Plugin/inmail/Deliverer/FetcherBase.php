<?php

namespace Drupal\inmail\Plugin\inmail\Deliverer;

use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for mail fetchers.
 *
 * This provides dumb implementations for most methods, but leaves
 * ::update() and ::fetchUnprocessedMessages() abstract.
 *
 * A fetcher additionally needs to implement ::buildConfigurationForm to offer
 * settings to configure the connection.
 *
 * @ingroup deliverer
 */
abstract class FetcherBase extends DelivererBase implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    // Merge with defaults.
    parent::__construct($configuration + $this->defaultConfiguration(), $plugin_id, $plugin_definition);
  }

  /**
   * Update the number of remaining messages to fetch.
   *
   * @param int $count
   *   The number of remaining messages.
   */
  public function setUnprocessedCount($count) {
    \Drupal::state()->set($this->makeStateKey('unprocessed_count'), $count);
  }

  /**
   * Update the total number of messages.
   *
   * @param int $count
   *   Total number of messages.
   */
  protected function setTotalCount($count) {
    \Drupal::state()->set($this->makeStateKey('total_count'), $count);
  }

  /**
   * {@inheritdoc}
   */
  public function getUnprocessedCount() {
    return \Drupal::state()->get($this->makeStateKey('unprocessed_count'));
  }

  /**
   * {@inheritdoc}
   */
  public function setLastCheckedTime($timestamp) {
    \Drupal::state()->set($this->makeStateKey('last_checked'), $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCount() {
    return \Drupal::state()->get($this->makeStateKey('total_count'));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuota() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastCheckedTime() {
    return \Drupal::state()->get($this->makeStateKey('last_checked'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Reset state.
    $this->setLastCheckedTime(NULL);
    $this->setUnprocessedCount(NULL);
    $this->setTotalCount(NULL);
  }

  /**
   * Handles submit call of "Test connection" button.
   */
  public function submitTestConnection(array $form, FormStateInterface $form_state) {
    throw new \Exception('Implement submitTestConnection() method in a subclass.');
  }

  /**
   * Adds a "Test connection" button to a form.
   *
   * @return array
   *   A form array containing "Test connection" button.
   */
  public function addTestConnectionButton() {
    $form['test_connection'] = array(
      '#type' => 'submit',
      '#value' => t('Test connection'),
      '#submit' => array(
        array($this, 'submitTestConnection'),
      ),
      '#executes_submit_callback' => TRUE,
      '#ajax' => array(
        'callback' => '::getPluginContainerFormChild',
        'wrapper' => 'inmail-plugin',
      ),
    );

    return $form;
  }

}
