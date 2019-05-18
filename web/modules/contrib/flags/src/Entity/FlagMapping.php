<?php


namespace Drupal\flags\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the flag mapping entity.
 *
 * Inherit from this class and add config entity annotation.
 */
abstract class FlagMapping extends ConfigEntityBase {

  /**
   * Source language code.
   *
   * @var string
   */
  protected $source;

  /**
   * Mapping UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Target territory flag.
   *
   * @var string
   */
  protected $flag;

  /**
   * @inheritDoc
   *
   * This method is required because for some reason entity_keys in config entity annotation are ignored.
   */
  public function id() {
    return $this->source;
  }

  /**
   * Sets ID.
   *
   * This method is required because for some reason entity_keys in config entity annotation are ignored.
   *
   * @param $id
   *
   * @return $this
   */
  public function setId($id) {
    $this->source = $id;

    return $this;
  }

  /**
   * Gets source of the mapping.
   *
   * @return string
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Sets source for the mapping.
   *
   * @param string $source
   *
   * @return FlagMapping
   */
  public function setSource($source) {
    $this->source = $source;

    return $this;
  }

  /**
   * Gets uuid.
   *
   * @return string
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Sets uuid.
   *
   * @param string $uuid
   *
   * @return FlagMapping
   */
  public function setUuid($uuid) {
    $this->uuid = $uuid;

    return $this;
  }

  /**
   * Gets target flag.
   *
   * @return string
   */
  public function getFlag() {
    return $this->flag;
  }

  /**
   * Sets target flag.
   *
   * @param string $flag
   *
   * @return FlagMapping
   */
  public function setFlag($flag) {
    // Make sure that the flag is lowercase.
    $this->flag = strtolower($flag);

    return $this;
  }

}
