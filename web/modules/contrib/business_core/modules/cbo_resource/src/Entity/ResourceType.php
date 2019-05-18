<?php

namespace Drupal\cbo_resource\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\cbo_resource\ResourceTypeInterface;

/**
 * Defines the Resource type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "resource_type",
 *   label = @Translation("Resource type"),
 *   handlers = {
 *     "access" = "Drupal\cbo_resource\ResourceTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\cbo_resource\ResourceTypeForm",
 *       "delete" = "Drupal\cbo_resource\Form\ResourceTypeDeleteConfirm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\cbo_resource\ResourceTypeListBuilder",
 *   },
 *   admin_permission = "administer resource types",
 *   config_prefix = "type",
 *   bundle_of = "resource",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/resource/type/add",
 *     "edit-form" = "/admin/resource/type/{resource_type}",
 *     "delete-form" = "/admin/resource/type/{resource_type}/delete",
 *     "collection" = "/admin/resource/type",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class ResourceType extends ConfigEntityBundleBase implements ResourceTypeInterface {

  /**
   * The machine name of this Resource type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the Resource type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this Resource type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('resource.type.locked');
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
