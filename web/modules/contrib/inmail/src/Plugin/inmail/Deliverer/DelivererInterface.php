<?php

namespace Drupal\inmail\Plugin\inmail\Deliverer;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines methods for deliverers.
 *
 * Deliverers provide new messages from a specific source to Inmail.
 *
 * @ingroup deliverer
 */
interface DelivererInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Returns the deliverer label.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   *   The deliverer label.
   */
  public function getLabel();

  /**
   * Returns the number of processed messages.
   *
   * @return int|null
   *   Number of processed messages, or NULL if it is unknown.
   */
  public function getProcessedCount();

  /**
   * Sets the number of processed messages.
   *
   * @param int $count
   *   The number of messages.
   */
  public function setProcessedCount($count);

  /**
   * Notify deliverer about successful processing of the message.
   *
   * @param string $key
   *   Key of the message that was processed.
   */
  public function success($key);

}
