<?php

namespace Drupal\bibcite_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Contributor category entities.
 */
interface ContributorCategoryInterface extends ConfigEntityInterface {

  /**
   * Get weight of the Contributor category.
   *
   * @return int
   *   Weight of the Contributor category.
   */
  public function getWeight();

  /**
   * Set weight of the Contributor category.
   *
   * @param int $weight
   *   New weight of the Contributor category.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorCategoryInterface
   *   The called Contributor category object.
   */
  public function setWeight($weight);

}
