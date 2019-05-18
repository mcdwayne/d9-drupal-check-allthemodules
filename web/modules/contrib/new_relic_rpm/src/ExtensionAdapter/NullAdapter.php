<?php

namespace Drupal\new_relic_rpm\ExtensionAdapter;

/**
 * Default new relic adapter.
 */
class NullAdapter implements NewRelicAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function setTransactionState($state) {}

  /**
   * {@inheritdoc}
   */
  public function logException(\Exception $e) {}

  /**
   * {@inheritdoc}
   */
  public function logError($message) {}

  /**
   * {@inheritdoc}
   */
  public function addCustomParameter($key, $value) {}

  /**
   * {@inheritdoc}
   */
  public function setTransactionName($name) {}

  /**
   * {@inheritdoc}
   */
  public function recordCustomEvent($name, array $attributes) {}

}
