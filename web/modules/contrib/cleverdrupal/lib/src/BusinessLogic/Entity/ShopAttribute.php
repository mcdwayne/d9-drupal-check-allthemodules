<?php

namespace CleverReach\BusinessLogic\Entity;

/**
 *
 */
class ShopAttribute {

  /**
   * @var string
   */
  private $description = '';

  /**
   * @var string
   */
  private $preview_value = '';

  /**
   * @var string
   */
  private $default_value = '';

  /**
   * @param string $description
   */
  public function setDescription($description) {
    $this->description = $description;
  }

  /**
   * @param string $preview_value
   */
  public function setPreviewValue($preview_value) {
    $this->preview_value = $preview_value;
  }

  /**
   * @param string $default_value
   */
  public function setDefaultValue($default_value) {
    $this->default_value = $default_value;
  }

  /**
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @return string
   */
  public function getPreviewValue() {
    return $this->preview_value;
  }

  /**
   * @return string
   */
  public function getDefaultValue() {
    return $this->default_value;
  }

}
