<?php

namespace Drupal\resources\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Resources type entity.
 *
 * @ConfigEntityType(
 *   id = "resources_type",
 *   label = @Translation("Resources type"),
 *   label_collection = @Translation("Resources types"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\resources\ResourcesTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\resources\Form\ResourcesTypeForm",
 *       "edit" = "Drupal\resources\Form\ResourcesTypeForm",
 *       "delete" = "Drupal\resources\Form\ResourcesTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\resources\ResourcesTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer resources entities",
 *   bundle_of = "resources",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/idcp/resources/type/{resources_type}",
 *     "add-form" = "/admin/idcp/resources/type/add",
 *     "edit-form" = "/admin/idcp/resources/type/{resources_type}/edit",
 *     "delete-form" = "/admin/idcp/resources/type/{resources_type}/delete",
 *     "collection" = "/admin/idcp/resources/type"
 *   }
 * )
 */
class ResourcesType extends ConfigEntityBase implements ResourcesTypeInterface {

  /**
   * The Resources type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Resources type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The default display method is outer for customer, but false not to customer,
   *   only for employee could see this type.
   * @var bool
   */
  protected $display;

  /**
   * @return bool
   */
  public function getDisplay() {
    return $this->display;
  }
}
