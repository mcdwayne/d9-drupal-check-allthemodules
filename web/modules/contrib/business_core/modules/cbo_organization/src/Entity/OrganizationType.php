<?php

namespace Drupal\cbo_organization\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\cbo_organization\OrganizationTypeInterface;

/**
 * Defines the Organization type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "organization_type",
 *   label = @Translation("Organization type"),
 *   label_collection = @Translation("Organization types"),
 *   handlers = {
 *     "access" = "Drupal\cbo_organization\OrganizationTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\cbo_organization\OrganizationTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\cbo_organization\OrganizationTypeListBuilder",
 *   },
 *   admin_permission = "administer organization types",
 *   config_prefix = "type",
 *   bundle_of = "organization",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/organization/type/add",
 *     "edit-form" = "/admin/organization/type/{organization_type}",
 *     "delete-form" = "/admin/organization/type/{organization_type}/delete",
 *     "collection" = "/admin/organization/type",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class OrganizationType extends ConfigEntityBundleBase implements OrganizationTypeInterface {

  /**
   * The machine name of this Organization type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the Organization type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this Organization type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('organization.type.locked');
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
