<?php

namespace Drupal\micro_site\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Site type entities.
 */
interface SiteTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this node type.
   */
  public function getDescription();

  /**
   * The site type should have a menu auto created ?
   *
   * @return boolean
   *   TRUE if a menu must be created with the site.
   */
  public function getMenu();

  /**
   * Sets the has Menu property.
   *
   * @param boolean $menu
   *   A boolean which indicates if the site type must have a menu.
   *
   * @return \Drupal\micro_site\Entity\SiteTypeInterface
   *   The SiteType.
   */
  public function setMenu($menu);

  /**
   * The site type should have a vocabulary auto created ?
   *
   * @return boolean
   *   TRUE if a vocabulary must be created with the site.
   */
  public function getVocabulary();

  /**
   * Sets the has Vocabualary property.
   *
   * @param boolean $vocabulary
   *   A boolean which indicates if the site type must have a vocabulary.
   *
   * @return \Drupal\micro_site\Entity\SiteTypeInterface
   *   The SiteType.
   */
  public function setVocabulary($vocabulary);

  /**
   * The site type can have users assigned to it ?
   *
   * @return boolean
   *   TRUE if site can manage users.
   */
  public function getUsersManagement();

  /**
   * Sets the usersManagement property.
   *
   * @param boolean $usersManagement
   *   A boolean which indicates if the site type can have users assigned to it.
   *
   * @return \Drupal\micro_site\Entity\SiteTypeInterface
   *   The SiteType.
   */
  public function setUsersManagement($usersManagement);

  /**
   * Gets the node types.
   *
   * @return array
   *   The node types which can be used by the entity.
   */
  public function getTypes();

  /**
   * Sets the node types.
   *
   * @param array $types
   *   The node types.
   *
   * @return \Drupal\micro_site\Entity\SiteTypeInterface
   *   The SiteType.
   */
  public function setTypes($types);

  /**
   * Gets the vocabularies.
   *
   * @return array
   *   The vocabularies which can be used by the entity.
   */
  public function getVocabularies();

  /**
   * Sets the vocabularies.
   *
   * @param array $vocabularies
   *   The vocabularies.
   *
   * @return \Drupal\micro_site\Entity\SiteTypeInterface
   *   The SiteType.
   */
  public function setVocabularies($vocabularies);

  /**
   * Gets the node types form to display as a tab.
   *
   * @return array
   *   The node types form to display as a tab.
   */
  public function getTypesTab();

  /**
   * Sets the node types form to display as a tab.
   *
   * @param array $types
   *   The node types.
   *
   * @return \Drupal\micro_site\Entity\SiteTypeInterface
   *   The SiteType.
   */
  public function setTypesTab($typesTab);

}
