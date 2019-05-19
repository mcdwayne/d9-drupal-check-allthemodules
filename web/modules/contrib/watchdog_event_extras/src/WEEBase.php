<?php

namespace Drupal\watchdog_event_extras;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for WEE plugin types.
 */
abstract class WEEBase extends PluginBase implements WEEInterface {

  /**
   * {@inheritdoc}
   */
  public function id() {
    // Retrieve the @id property from the annotation and return it.
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\watchdog_event_extras\WEEInterface::title()
   */
  public function title() {
    // Retrieve the @title property from the annotation and return it.
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\watchdog_event_extras\WEEInterface::attached()
   */
  public function attached(&$attached, $dblog) {
    // Do nothing by default.
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\watchdog_event_extras\WEEInterface::markup()
   */
  public function markup($dblog) {
    // Return nothing by default.
    return '';
  }

}
