<?php

namespace Drupal\configelement\EditableConfig;

/**
 * Class EditableConfigWrapper
 *
 * Wraps a config object, can be shared by multiple config items, and can
 * autosave, triggered by EditableConfigItemFactory::triggetAutosave
 *
 * @package Drupal\configelement\EditableConfig
 */
interface EditableConfigWrapperInterface {

  /**
   * Get value.
   *
   * @param string $key
   *
   * @return mixed
   */
  public function get($key);

  /**
   * Set value.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   */
  public function set($key, $value);

  /**
   * Check if a key exists.
   *
   * @param string $key
   * @return bool
   */
  public function has($key);

  /**
   * Trigger autosave.
   *
   * @internal Use EditableConfigItemFactory::triggerAutosave
   */
  public function save();

  /**
   * Get schema wrapper / typed data of the wrapped config.
   *
   * @see \Drupal\Core\Config\StorableConfigBase::getSchemaWrapper (protected)
   * @todo Upstream: That function should be public.
   *
   * @param string $propertyPath
   *
   * @return \Drupal\Core\Config\Schema\TypedConfigInterface
   */
  public function getSchemaWrapper($propertyPath = '');

  /**
   * Validate config.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   */
  public function validate();

  /**
   * Add this as a cacheable dependency.
   *
   * @param array $element
   *   The render element.
   *
   * @return
   */
  public function addCachableDependencyTo(array &$element);
}