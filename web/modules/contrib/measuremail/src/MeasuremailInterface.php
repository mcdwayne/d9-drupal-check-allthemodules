<?php

namespace Drupal\measuremail;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a measuremail entity.
 */
interface MeasuremailInterface extends ConfigEntityInterface {

  /**
   * Returns the measuremail settings.
   *
   * @return array
   *   A structured array containing all the measuremail settings.
   */
  public function getSettings();

  /**
   * Sets the measuremail settings.
   *
   * @param array $settings
   *   The structured array containing all the measuremail setting.
   *
   * @return $this
   */
  public function setSettings(array $settings);

  /**
   * Returns the measuremail settings for a given key.
   *
   * @param string $key
   *   The key of the setting to retrieve.
   * @param bool $default
   *   Flag to lookup the default settings from 'measuremail.settings' config.
   *   Only used when rendering measuremail.
   *
   * @return mixed
   *   The settings value, or NULL if no settings exists.
   */
  public function getSetting($key, $default = FALSE);

  /**
   * Sets a measuremail setting for a given key.
   *
   * @param string $key
   *   The key of the setting to store.
   * @param mixed $value
   *   The data to store.
   *
   * @return $this
   */
  public function setSetting($key, $value);

  /**
   * Returns a specific measuremail element.
   *
   * @param string $element
   *   The measuremail element ID.
   *
   * @return \Drupal\measuremail\MeasuremailElementsInterface
   *   The measuremail element object.
   */
  public function getElement($element);

  /**
   * Returns the measuremail elements for this form.
   *
   * @return \Drupal\measuremail\MeasuremailElementsPluginCollection|\Drupal\measuremail\MeasuremailElementsInterface[]
   *   The measuremail element plugin collection.
   */
  public function getElements();

  /**
   * Saves a measuremail element for this form.
   *
   * @param array $configuration
   *   An array of measuremail element configuration.
   *
   * @return string
   *   The measuremail element ID.
   */
  public function addMeasuremailElement(array $configuration);

  /**
   * Deletes an measuremail element from this form.
   *
   * @param \Drupal\measuremail\MeasuremailElementsInterface $element
   *   The measuremail element object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @return $this
   */
  public function deleteMeasuremailElement(MeasuremailElementsInterface $element);

}
