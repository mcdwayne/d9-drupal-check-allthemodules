<?php

namespace Drupal\api_tokens;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for the API token plugins.
 *
 * API tokens are discovered through annotations, which may contain the
 * following definition properties:
 * - id: (required) The API token ID.
 * - label: (required) The administrative label of the API token.
 * - description: The administrative description of the API token.
 */
interface ApiTokenPluginInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface, RefinableCacheableDependencyInterface {

  /**
   * Returns the API token ID.
   *
   * @return string
   */
  public function id();

  /**
   * Returns the administrative label of the API token.
   *
   * @return string
   */
  public function label();

  /**
   * Returns the administrative description of the API token.
   *
   * @return string
   */
  public function description();

  /**
   * Returns the name of the provider that owns this API token.
   *
   * @return string
   */
  public function provider();

  /**
   * Returns the API token string.
   *
   * @return string
   */
  public function token();

  /**
   * Returns the API token parameter string.
   *
   * @return string
   */
  public function paramString();

  /**
   * Returns the API token parameters.
   *
   * @return array
   */
  public function params();

  /**
   * Returns the API token parameters hash.
   *
   * @return string
   */
  public function hash();

  /**
   * Returns the API token build method reflection object.
   *
   * @return \ReflectionMethod|null
   */
  public function reflector();

  /**
   * Performs one-time context-independent validation of the API token.
   *
   * @return bool
   */
  public function validateToken();

  /**
   * Returns processed API token build.
   *
   * @return array
   *   A renderable array for the API token output.
   */
  public function process();

  /**
   * Returns a build to replace the API token with in case of validation fail.
   *
   * @return array
   *   A renderable array.
   */
  public function fallback();

  /**
   * Returns a #lazy_builder placeholder for the API tokens filter.
   *
   * @return array
   *   A renderable array representing a placeholder.
   */
  public function placeholder();

  /**
   * The #lazy_builder callback.
   *
   * @param string $id
   *   The API token ID.
   * @param string $params
   *   Parameters part of the API token string.
   * @param bool $valid
   *   Whether the API token is valid.
   *
   * @return array
   *   A renderable array for the API token output.
   */
  public static function lazyBuilder($id, $params, $valid);

  /**
   * Returns the API token build.
   *
   * This method can not be added to the interface due to variable parameters.
   *
   * Cacheability metadata can be attached to a renderable array or added using
   * \Drupal\Core\Cache\RefinableCacheableDependencyInterface methods.
   *
   * @return array
   *   A renderable array.
   *
  public function build(...);
  */

}
