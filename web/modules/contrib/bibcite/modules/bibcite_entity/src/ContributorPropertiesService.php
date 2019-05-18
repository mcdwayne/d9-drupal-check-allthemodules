<?php

namespace Drupal\bibcite_entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ContributorPropertiesService.
 *
 * @package Drupal\bibcite
 */
class ContributorPropertiesService implements ContributorPropertiesServiceInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Construct new UIOverrideProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get first element of category list.
   *
   * @return string|null
   *   Default category string.
   */
  public function getDefaultCategory() {
    $list = $this->getCategories();
    if (!empty($list)) {
      reset($list);
      return key($list);
    }
    else {
      return NULL;
    }
  }

  /**
   * List of roles.
   *
   * @var array
   */
  private $rolesList;

  /**
   * List of categories.
   *
   * @var array
   */
  private $categoriesList;

  /**
   * Get first element of role list.
   *
   * @return string|null
   *   Default role string.
   */
  public function getDefaultRole() {
    $list = $this->getRoles();
    if (!empty($list)) {
      reset($list);
      return key($list);
    }
    else {
      return NULL;
    }
  }

  /**
   * Get list of contributor categories.
   *
   * @return array
   *   Contributor categories.
   */
  public function getCategories() {
    if (!isset($this->categoriesList)) {
      $entities = $this->entityTypeManager->getStorage('bibcite_contributor_category')->loadMultiple();
      uasort($entities, [$this, 'sortByWeightProperty']);

      $this->categoriesList = array_map(function ($entity) {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        return $entity->label();
      }, $entities);
    }
    return $this->categoriesList;
  }

  /**
   * Get list of contributor roles.
   *
   * @return array
   *   Contributor roles.
   */
  public function getRoles() {
    if (!isset($this->rolesList)) {
      $entities = $this->entityTypeManager->getStorage('bibcite_contributor_role')->loadMultiple();
      uasort($entities, [$this, 'sortByWeightProperty']);

      $this->rolesList = array_map(function ($entity) {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        return $entity->label();
      }, $entities);
    }
    return $this->rolesList;
  }

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
  public function sortByWeightProperty(ConfigEntityInterface $entity_first, ConfigEntityInterface $entity_second) {
    $weight_first = $entity_first->get('weight');
    $weight_second = $entity_second->get('weight');

    if ($weight_first == $weight_second) {
      return 0;
    }
    return ($weight_first < $weight_second) ? -1 : 1;
  }

}
