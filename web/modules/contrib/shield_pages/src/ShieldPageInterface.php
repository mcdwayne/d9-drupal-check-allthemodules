<?php

namespace Drupal\shield_pages;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining shield page entities.
 */
interface ShieldPageInterface extends ConfigEntityInterface {

  /**
   * Gets the path of this shield page.
   *
   * @return string
   */
  public function getPath();

  /**
   * Sets the path of this shield page.
   *
   * @param string $path
   *
   * @return $this
   */
  public function setPath($path);

  /**
   * Gets the weight of this shield page (compared to other shield pages).
   *
   * @return int
   */
  public function getWeight();

  /**
   * Sets the weight of this shield page (compared to other shield pages).
   *
   * @param int $weight
   *   The weight of the variant.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the passwords of this shield page.
   *
   * @return array
   */
  public function getPasswords();

  /**
   * Sets the passwords of this shield page.
   *
   * @param array $passwords
   *
   * @return $this
   */
  public function setPasswords($passwords);
}
