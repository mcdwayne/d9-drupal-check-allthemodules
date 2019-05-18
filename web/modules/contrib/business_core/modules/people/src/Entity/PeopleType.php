<?php

namespace Drupal\people\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\people\PeopleTypeInterface;

/**
 * Defines the People type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "people_type",
 *   label = @Translation("People type"),
 *   label_collection = @Translation("People types"),
 *   handlers = {
 *     "access" = "Drupal\people\PeopleTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\people\PeopleTypeForm",
 *       "delete" = "Drupal\people\Form\PeopleTypeDeleteConfirm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\cbo\CboConfigEntityListBuilder",
 *   },
 *   admin_permission = "administer people types",
 *   config_prefix = "type",
 *   bundle_of = "people",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/people/type/add",
 *     "edit-form" = "/admin/people/type/{people_type}/edit",
 *     "delete-form" = "/admin/people/type/{people_type}/delete",
 *     "collection" = "/admin/people/type",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class PeopleType extends ConfigEntityBundleBase implements PeopleTypeInterface {

  /**
   * The machine name of this People type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the People type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this People type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('people.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
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
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
