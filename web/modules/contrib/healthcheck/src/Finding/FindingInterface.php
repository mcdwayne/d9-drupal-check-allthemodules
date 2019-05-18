<?php

namespace Drupal\healthcheck\Finding;

interface FindingInterface {

  /**
   * Get the Healthcheck plugin which discovered this finding.
   *
   * @return \Drupal\healthcheck\Plugin\HealthcheckPluginInterface
   *   The healthcheck plugin who generated this collection.
   */
  public function getCheck();

  /**
   * Gets a identifying key for the finding.
   *
   * @return int|string
   */
  public function getKey();

  /**
   * The status of the finding.
   *
   * @return int
   *
   * @see \Drupal\healthcheck\Finding\FindingStatus
   */
  public function getStatus();

  /**
   * Get the display label for the finding.
   *
   * @return string
   *   The display label
   */
  public function getLabel();

  /**
   * Set the label for the finding.
   *
   * @param string $label
   *   The label to set for the finding.
   */
  public function setLabel($label);

  /**
   * Gets the message text.
   *
   * @return string
   */
  public function getMessage();

  /**
   * Sets the message text.
   *
   * @param string $message
   *   The message to set for the finding.
   */
  public function setMessage($message);

  /**
   * Returns the finding as an associative array.
   *
   * @return array
   */
  public function toArray();

  /**
   * Adds custom data to the finding results.
   *
   * @param string $key
   *   The unique key to refer to the value.
   * @param mixed $value
   *   The value to set.
   *
   * @return mixed
   */
  public function addData($key, $value);

  /**
   * Gets the custom data value for the given key.
   *
   * @param $key
   *   The unique key assigned to the data value.
   *
   * @return mixed|bool
   *   The custom value if found, FALSE otherwise.
   */
  public function getData($key);

  /**
   * Get all the custom data values as an array.
   *
   * @return array
   *   An array of all custom data values, keyed by key.
   */
  public function getAllData();
}
