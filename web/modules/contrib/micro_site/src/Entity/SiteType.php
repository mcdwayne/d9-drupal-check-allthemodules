<?php

namespace Drupal\micro_site\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Site type entity.
 *
 * @ConfigEntityType(
 *   id = "site_type",
 *   label = @Translation("Site type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\micro_site\SiteTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\micro_site\Form\SiteTypeForm",
 *       "edit" = "Drupal\micro_site\Form\SiteTypeForm",
 *       "delete" = "Drupal\micro_site\Form\SiteTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\micro_site\SiteTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "site_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "site",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/sites/{site_type}",
 *     "add-form" = "/admin/structure/sites/add",
 *     "edit-form" = "/admin/structure/sites/{site_type}/edit",
 *     "delete-form" = "/admin/structure/sites/{site_type}/delete",
 *     "collection" = "/admin/structure/sites"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "menu",
 *     "vocabulary",
 *     "usersManagement",
 *     "types",
 *     "typesTab",
 *     "vocabularies"
 *   }
 * )
 */
class SiteType extends ConfigEntityBundleBase implements SiteTypeInterface {

  /**
   * The Site type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Site type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this node type.
   *
   * @var string
   */
  protected $description;

  /**
   * A boolean which indicate if the site type has a menu created automatically.
   *
   * @var boolean
   */
  protected $menu;

  /**
   * A boolean which indicate if the site type has a vocabulary created automatically.
   *
   * @var boolean
   */
  protected $vocabulary;

  /**
   * A boolean which indicate if the site type can manage users assigned to the site.
   *
   * @var boolean
   */
  protected $usersManagement;

  /**
   * The node types which can be used on this site type.
   *
   * @var array
   */
  protected $types = [];

  /**
   * The node types form to display as a tab.
   *
   * @var array
   */
  protected $typesTab = [];

  /**
   * The vocabularies which can be used on this site type.
   *
   * @var array
   */
  protected $vocabularies = [];

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    return $this->types;
  }

  /**
   * {@inheritdoc}
   */
  public function setTypes($types) {
    $this->types = $types;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypesTab() {
    return $this->typesTab;
  }

  /**
   * {@inheritdoc}
   */
  public function setTypesTab($typesTab) {
    $this->typesTab = $typesTab;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabularies() {
    return $this->vocabularies;
  }

  /**
   * {@inheritdoc}
   */
  public function setVocabularies($vocabularies) {
    $this->vocabularies = $vocabularies;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenu() {
    return $this->menu;
  }

  /**
   * {@inheritdoc}
   */
  public function setMenu($menu) {
    $this->menu = $menu;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabulary() {
    return $this->vocabulary;
  }

  /**
   * {@inheritdoc}
   */
  public function setVocabulary($vocabulary) {
    $this->vocabulary = $vocabulary;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsersManagement() {
    return $this->usersManagement;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsersManagement($usersManagement) {
    $this->usersManagement = $usersManagement;
    return $this;
  }

}
