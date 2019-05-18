<?php

namespace Drupal\chatbot_api;

/**
 * Request interface for Intent API.
 *
 * Request wrapper for services who want to work with Intent API Intents.
 */
interface IntentRequestInterface {

  /**
   * Get Intent name.
   *
   * @return string
   *   Return the Intent name.
   */
  public function getIntentName();

  /**
   * Get session attribute.
   *
   * @param string $name
   *   The attribute name to be returned.
   * @param mixed $default
   *   A default value if the attribute is not found.
   *
   * @return mixed
   *   The attribute value.
   */
  public function getIntentAttribute($name, $default = NULL);

  /**
   * Get session slot.
   *
   * @param string $name
   *   The slot name to be returned.
   * @param mixed $default
   *   A default value if the slot is not found.
   *
   * @return string
   *   The slot value.
   */
  public function getIntentSlot($name, $default = NULL);

}
