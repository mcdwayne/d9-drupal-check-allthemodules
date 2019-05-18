<?php

namespace Drupal\js\Plugin\Js;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * JsCallbackInterface.
 */
interface JsCallbackInterface extends PluginInspectionInterface {

  /**
   * Access callback.
   *
   * @return bool
   *   TRUE if the callback can be accessed, FALSE otherwise.
   */
  public function access();

  /**
   * Message to show when access to the callback has been denied.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function accessDeniedMessage();

  /**
   * Message to show when user is anonymous and callback requires CSRF token.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function anonymousUserMessage();

  /**
   * Calls a method on the callback, providing necessary converted parameters.
   *
   * @param string $method
   *   The method name to call.
   *
   * @return mixed
   *   The result from calling the method.
   */
  public function call($method);

  /**
   * Flag indicating whether or not the callback should capture printed output.
   *
   * @return bool
   */
  public function captureOutput();

  /**
   * Flag indicating whether callback should validate a CSRF token.
   *
   * @return bool
   */
  public function csrfToken();

  /**
   * Executes the callback.
   *
   * @param mixed ...
   *   Any number of arguments can be passed here.
   *
   * @return mixed
   *   The content to return.
   */
  public function execute();

  /**
   * An indexed array of allowed HTTP methods.
   *
   * @return array
   */
  public function getAllowedMethods();

  /**
   * An associative array of parameters.
   *
   * @return array
   */
  public function getParameters();

  /**
   * Retrieves the callback's response handler.
   *
   * @return \Drupal\js\JsResponse
   */
  public function getResponse();

  /**
   * The human readable title of the callback, if set.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The title or NULL if not set.
   */
  public function getTitle();

  /**
   * Message to show when an invalid CSRF token was provided.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function invalidTokenMessage();

  /**
   * Message to show when a callback was requested with an invalid HTTP method.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function methodNotAllowedMessage();

  /**
   * Sets the title for the callback.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $title
   *   The title to set.
   *
   * @return $this
   */
  public function setTitle($title = '');

  /**
   * Validates the callback.
   *
   * @param mixed ...
   *   Any number of arguments can be passed here.
   *
   * @return mixed
   *   The content to return.
   */
  public function validate();

}
