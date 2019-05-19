<?php

namespace Drupal\warmer\Plugin;

use Drupal\Core\Form\SubformStateInterface;

interface WarmerInterface {

  /**
   * Returns the batch size for the warming operation.
   *
   * @return int
   *   The size.
   */
  public function getBatchSize();

  /**
   * Returns the frequency for the warming operation.
   *
   * @return int
   *   The frequency in seconds.
   */
  public function getFrequency();

  /**
   * Loads multiple items based on their IDs.
   *
   * @param array
   *   The item IDs.
   *
   * @return array
   *   The loaded items.
   */
  public function loadMultiple(array $ids = []);

  /**
   * Warms multiple items.
   *
   * @param array $items
   *   The items to warm.
   *
   * @return int
   *   The number of items that were successfully warmed.
   */
  public function warmMultiple(array $items = []);

  /**
   * Builds the next batch of IDs based on a position cursor.
   *
   * @param mixed $cursor
   *   The position of the last generate batch.
   *
   * @return array
   *   The array of item IDs.
   */
  public function buildIdsBatch($cursor);

  /**
   * Checks if the plugin should warm in this particular moment.
   *
   * @return bool
   *   TRUE if the warmer is active. FALSE otherwise.
   */
  public function isActive();

  /**
   * Marks a warmer as enqueued.
   *
   * @return bool
   *   TRUE if the warmer is active. FALSE otherwise.
   */
  public function markAsEnqueued();

  /**
   * Adds additional form elements to the configuration form.
   *
   * @param array $form
   *   The configuration form to alter for the this plugin settings.
   * @param \Drupal\Core\Form\SubformStateInterface $form_state
   *   The form state for the plugin settings.
   *
   * @return array
   *   The form with additional elements.
   */
  public function addMoreConfigurationFormElements(array $form, SubformStateInterface $form_state);

}
