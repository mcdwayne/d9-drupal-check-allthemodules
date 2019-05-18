<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Entity\MembershipType;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Membership type entity.
 *
 * @ConfigEntityType(
 *   id = "membership_entity_type",
 *   label = @Translation("Membership type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\membership_entity\Entity\MembershipType\MembershipTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\membership_entity\Form\MembershipTypeForm",
 *       "edit" = "Drupal\membership_entity\Form\MembershipTypeForm",
 *       "delete" = "Drupal\membership_entity\Form\MembershipTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\membership_entity\Entity\MembershipType\MembershipTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer membership entities",
 *   bundle_of = "membership_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "canonical" = "/admin/memberships/config/types/{membership_entity_type}",
 *     "collection" = "/admin/memberships/config/types",
 *     "add-form" = "/admin/memberships/config/types/add",
 *     "edit-form" = "/admin/memberships/config/types/{membership_entity_type}/edit",
 *     "delete-form" = "/admin/memberships/config/types/{membership_entity_type}/delete"
 *   }
 * )
 */
class MembershipType extends ConfigEntityBundleBase implements MembershipTypeInterface {
  /**
   * The machine name of this membership type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the membership type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this membership type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->description;
  }
}
