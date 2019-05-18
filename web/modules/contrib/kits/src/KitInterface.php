<?php

namespace Drupal\kits;

use Drupal\kits\Services\KitsInterface;

/**
 * Interface KitInterface
 *
 * @package Drupal\kits
 */
interface KitInterface {
  /**
   * Whether the child Kits of this Kit would be separate, but 'grouped' with
   * this kit in the render array.
   */
  const IS_CHILDREN_GROUPED = FALSE;

  /**
   * @param KitsInterface $kitsService
   * @param null $id
   * @param array $parameters
   * @param array $context
   *
   * @return static
   */
  public static function create(KitsInterface $kitsService, $id = NULL, array $parameters = [], array $context = []);

  /**
   * @param \Drupal\kits\KitInterface $kit
   *
   * @return mixed
   */
  public function append(KitInterface $kit);

  /**
   * @param string $parentID
   * @return static
   */
  public function appendParent($parentID);

  /**
   * Exclude a particular parameter when building the render array.
   *
   * @param string $parameter
   *
   * @return static
   */
  public function excludeParameter($parameter);

  /**
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   */
  public function get($key, $default = NULL);

  /**
   * @return array
   */
  public function getArray();

  /**
   * @return string
   */
  public function getID();

  /**
   * @return bool
   */
  public function getChildrenArray();

  /**
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   */
  public function getContext($key, $default = NULL);

  /**
   * @return array
   */
  public function getParents();

  /**
   * @param string $key
   *
   * @return bool
   */
  public function has($key);

  /**
   * @param string $key
   * @param mixed $value
   *
   * @return static
   */
  public function set($key, $value);

  /**
   * @param string $key
   * @param mixed $value
   *
   * @return static
   */
  public function setContext($key, $value);

  /**
   * @param string $value
   *
   * @return static
   */
  public function setID($value);

  /**
   * @param string $group
   *
   * @return static
   */
  public function setGroup($group);
}
