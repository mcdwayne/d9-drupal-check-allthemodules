<?php

namespace Drupal\entity_switcher\Entity;

/**
 * Provides an interface defining a entity_switcher_setting entity.
 */
interface SwitcherInterface {

  /**
   * Returns the description for the switcher settings.
   *
   * @return string
   *   The description for the switcher settings.
   */
  public function getDescription();

  /**
   * Sets the description for the switcher settings.
   *
   * @param string $description
   *   The description for the switcher settings.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Sets the label for the switcher settings.
   *
   * @param string $label
   *   The label for the switcher settings.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Returns the off value for the switcher settings.
   *
   * @return string
   *   The off value for the switcher settings.
   */
  public function getDataOff();

  /**
   * Sets the off value for the switcher settings.
   *
   * @param string $data_off
   *   The off value for the switcher settings.
   *
   * @return $this
   */
  public function setDataOff($data_off);

  /**
   * Returns the on value for the switcher settings.
   *
   * @return string
   *   The on value for the switcher settings.
   */
  public function getDataOn();

  /**
   * Sets the on value for the switcher settings.
   *
   * @param string $data_on
   *   The on value for the switcher settings.
   *
   * @return $this
   */
  public function setDataOn($data_on);

  /**
   * Returns the default value for the switcher settings.
   *
   * @return string
   *   The default value for the switcher settings.
   */
  public function getDefaultValue();

  /**
   * Sets the default value for the switcher settings.
   *
   * @param string $default_value
   *   The default value for the switcher settings.
   *
   * @return $this
   */
  public function setDefaultValue($default_value);

  /**
   * Returns the container classes for the switcher settings.
   *
   * @return string
   *   The container classes for the switcher settings.
   */
  public function getContainerClasses();

  /**
   * Sets the container classes for the switcher settings.
   *
   * @param string $container_classes
   *   The container classes for the switcher settings.
   *
   * @return $this
   */
  public function setContainerClasses($container_classes);

  /**
   * Returns the slider classes for the switcher settings.
   *
   * @return string
   *   The slider classes for the switcher settings.
   */
  public function getSliderClasses();

  /**
   * Sets the slider classes for the switcher settings.
   *
   * @param string $slider_classes
   *   The slider classes for the switcher settings.
   *
   * @return $this
   */
  public function setSliderClasses($slider_classes);

}