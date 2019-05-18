<?php

namespace Drupal\licensing\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the License type entity.
 *
 * @ConfigEntityType(
 *   id = "license_type",
 *   label = @Translation("License type"),
 *   handlers = {
 *     "list_builder" = "Drupal\licensing\LicenseTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\licensing\Form\LicenseTypeForm",
 *       "edit" = "Drupal\licensing\Form\LicenseTypeForm",
 *       "delete" = "Drupal\licensing\Form\LicenseTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\licensing\LicenseTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "license_type",
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "target_bundles",
 *     "target_entity_type",
 *     "roles",
 *     "exempt_roles"
 *   },
 *   admin_permission = "administer site configuration",
 *   bundle_of = "license",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/license_type/{license_type}",
 *     "add-form" = "/admin/structure/license_type/add",
 *     "edit-form" = "/admin/structure/license_type/{license_type}/edit",
 *     "delete-form" = "/admin/structure/license_type/{license_type}/delete",
 *     "collection" = "/admin/structure/license_type"
 *   }
 * )
 */
class LicenseType extends ConfigEntityBundleBase implements LicenseTypeInterface {

  /**
   * The License type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The License type label.
   *
   * @var string
   */
  protected $label;

}
