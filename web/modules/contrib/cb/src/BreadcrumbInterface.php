<?php

namespace Drupal\cb;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a chained breadcrumb entity.
 */
interface BreadcrumbInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the name of the breadcrumb.
   *
   * @return string
   *   The name of the breadcrumb.
   */
  public function getName();

  /**
   * Sets the name of the breadcrumb.
   *
   * @param int $name
   *   The breadcrumb's name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the weight of the breadcrumb.
   *
   * @return int
   *   The weight of the breadcrumb.
   */
  public function getWeight();

  /**
   * Gets the weight of the breadcrumb.
   *
   * @param int $weight
   *   The breadcrumb's weight.
   *
   * @return $this
   */
  public function setWeight($weight);

}
