<?php

namespace Drupal\global_gateway\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the flag mapping entity.
 *
 * Inherit from this class and add config entity annotation.
 */
abstract class RegionMapping extends ConfigEntityBase {

  /**
   * Region code.
   *
   * @var string
   */
  protected $region;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->region;
  }

  /**
   * Sets ID.
   *
   * This method is required because for some reason,
   * entity_keys in config entity annotation are ignored.
   *
   * @param string $id
   *   The region code.
   *
   * @return $this
   */
  public function setId($id) {
    $this->region = $id;

    return $this;
  }

  /**
   * Gets region of the mapping.
   *
   * @return string
   *   The region code.
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * Sets region for the mapping.
   *
   * @param string $region
   *   The region code.
   *
   * @return $this
   */
  public function setRegion($region) {
    $this->region = $region;

    return $this;
  }

}
