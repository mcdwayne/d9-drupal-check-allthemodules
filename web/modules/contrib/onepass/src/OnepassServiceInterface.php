<?php

namespace Drupal\onepass;

/**
 * Provides an interface defining a OnePass service.
 */
interface OnepassServiceInterface {

  /**
   * Return OnePass field name.
   *
   * @return string
   *   Field name.
   */
  public function getFieldName();

  /**
   * Return Onepass service short code.
   *
   * @return string
   *   OnePass short code.
   */
  public function getShortCode();

  /**
   * Return short code replacement rendering array.
   *
   * @param object $entity
   *   Entity object.
   *
   * @return array
   *   Short code replacement render array on success or empty array otherwise.
   */
  public function getShortCodeReplacement($entity);

  /**
   * Return Unique identifier for shortcode replacement.
   *
   * @param string $id
   *   Item or Entity identifier.
   *
   * @return string
   *   Unique identifier.
   */
  public function getShortCodeReplacementUniqueId($id);

  /**
   * Check is entity processing needed.
   *
   * @param object $entity
   *   Entity for check.
   * @param string $view_mode
   *   Entity view mode.
   *
   * @return bool
   *   Result of check.
   */
  public function processingNeeded($entity, $view_mode);

  /**
   * Check is OnePass integration enabled for requested bundle.
   *
   * @param string $bundle
   *   Node type.
   *
   * @return int
   *   Int 1 on success or int 0 otherwise.
   */
  public function bundleIntegrationEnabled($bundle);

  /**
   * Manage (CRUD) bundle integration.
   *
   * @param string $bundle
   *   Node type.
   * @param int $action
   *   Add = 1 or remove = 0.
   */
  public function manageBundleIntegration($bundle, $action);

  /**
   * Check is relation to OnePass service exists for requested entity.
   *
   * @param object $entity
   *   Entity object.
   *
   * @return int
   *   Int 1 on success or int 0 otherwise.
   */
  public function relationExists($entity);

  /**
   * Manage (CRUD) entity relation.
   *
   * @param object $entity
   *   Entity object.
   * @param int $action
   *   Add = 1 or remove = 0.
   */
  public function manageRelation($entity, $action);

  /**
   * Check is Paywall option enabled.
   *
   * @return int
   *   Int 1 on success or int 0 otherwise.
   */
  public function paywallEnabled();

  /**
   * Replace Onepass placeholder with it's markup.
   *
   * @param array $build
   *   Entity built display array.
   */
  public function prepareDisplay(&$build);

  /**
   * Remove Onepass placeholder from output.
   *
   * @param array $build
   *   Entity built display array.
   */
  public function removeShortCode(&$build);

  /**
   * Build a hash we can use to authenticate with OnePass.
   *
   * @param string $unique_identifier
   *   Article UUID or if UUID isn't active article node nid.
   * @param int $ts
   *   Timestamp.
   *
   * @return string
   *   Hash string.
   */
  public function buildHash($unique_identifier, $ts);

  /**
   * Check is current request valid request from 1Pass service server.
   */
  public function isRequestValid();

  /**
   * Prepare time for display on atoms feed.
   *
   * @param mixed $time
   *   Time for format.
   *
   * @return string
   *   Formatted date string.
   */
  public function formatDate($time);

  /**
   * Mark Entity for trim content (display without OnePass button).
   *
   * @param object $entity
   *   Entity object.
   *
   * @return object
   *   Marked for trim entity object.
   */
  public function markForTrim($entity);

  /**
   * Check is requested entity marked for trim.
   *
   * @param object $entity
   *   Entity object.
   *
   * @return bool
   *   Result of check.
   */
  public function trimNeeded($entity);

  /**
   * Remove trim mark for requested entity.
   *
   * @param object $entity
   *   Entity object.
   */
  public function cleanupTrimMark($entity);

}
