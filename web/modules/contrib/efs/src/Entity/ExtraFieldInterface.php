<?php

namespace Drupal\efs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Extra field entities.
 */
interface ExtraFieldInterface extends ConfigEntityInterface {

  /**
   * Get the plugin id.
   *
   * @return string
   *   The plugin id.
   */
  public function getPlugin();

  /**
   * Get the entity-type id.
   *
   * @return string
   *   The entity-type id.
   */
  public function getTargetEntityTypeId();

  /**
   * Get the entity bundle.
   *
   * @return string
   *   The entity bundle.
   */
  public function getBundle();

  /**
   * Get the display mode.
   *
   * @return string
   *   The display mode.
   */
  public function getMode();

  /**
   * Get the plugin settings.
   *
   * @return array
   *   The plugin settings.
   */
  public function getSettings();

  /**
   * Get a plugin setting.
   *
   * @param string $setting_name
   *   The setting key.
   *
   * @return mixed|null
   *   The setting value or NULL if not exists.
   */
  public function getSetting($setting_name);

  /**
   * Get the composite id.
   *
   * @return string
   *   The composite id.
   */
  public function composedId();

  /**
   * Get the display context.
   *
   * @return string
   *   The display context.
   */
  public function getContext();

  /**
   * Get the extra-field name.
   *
   * @return string
   *   The extra-field name.
   */
  public function getName();

  /**
   * Set the plugin settings.
   *
   * @param array $settings
   *   An array of settings.
   */
  public function setSettings(array $settings);

  /**
   * Set a plugin setting.
   *
   * @param string $setting_name
   *   Setting key.
   * @param mixed $value
   *   Setting value.
   *
   * @return \self
   *   Fluent setter.
   */
  public function setSetting($setting_name, $value);

}
