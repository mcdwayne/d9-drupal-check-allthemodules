<?php

namespace Drupal\js_manager\Entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the Javascript entity interface.
 */
interface JavascriptInterface extends EntityInterface {

  /**
   * Get the type of JS.
   *
   * @return string
   *   inline or external
   */
  public function getJsType();

  /**
   * Get the external JS.
   *
   * @return string
   *   External JS URL.
   */
  public function getExternalJs();

  /**
   * Get external JS async.
   *
   * @return bool
   *   Should the external JS be loaded asynchronously.
   */
  public function getExternalJsAsync();

  /**
   * Get the internal JS.
   *
   * @return string
   *   Inline JS URL.
   */
  public function getInlineJs();

  /**
   * Exclude on admin paths.
   *
   * @return bool
   *   Exclude on admin paths.
   */
  public function excludeAdmin();

  /**
   * Exclude on admin paths label.
   *
   * @return string
   *   Exclude on admin paths.
   */
  public function excludeAdminLabel();

  /**
   * Get the script weight.
   *
   * @return int
   *   Script weight
   */
  public function getWeight();

  /**
   * Get the script scope.
   *
   * @return string
   *   The script scope.
   */
  public function getScope();

  /**
   * Converts JavaScript to renderable array.
   *
   * @return array
   *   Renderable array
   */
  public function toRenderArray();

}
