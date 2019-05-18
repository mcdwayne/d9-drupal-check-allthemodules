<?php

namespace Drupal\bibcite_entity;

/**
 * Define an interface for manage contributor properties service.
 */

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface HelpInterface.
 *
 * @package Drupal\bibcite
 */
interface ContributorPropertiesServiceInterface {

  /**
   * Get first element of category list.
   *
   * @return string|null
   *   Default category string.
   */
  public function getDefaultCategory();

  /**
   * Get first element of role list.
   *
   * @return string|null
   *   Default role string.
   */
  public function getDefaultRole();

  /**
   * Get list of contributor categories.
   *
   * @return array
   *   Contributor categories.
   */
  public function getCategories();

  /**
   * Get list of contributor roles.
   *
   * @return array
   *   Contributor roles.
   */
  public function getRoles();

  /**
   * Sort callback for config entities with weight parameter.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity_first
   *   First entity to compare.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity_second
   *   Second entity to compare.
   *
   * @return int
   *   Sort result.
   */
  public function sortByWeightProperty(ConfigEntityInterface $entity_first, ConfigEntityInterface $entity_second);

}
