<?php

namespace Drupal\crm_core_contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;
use Drupal\crm_core_contact\ContactTypeInterface;

/**
 * CRM Organization Type Entity Class.
 *
 * @ConfigEntityType(
 *   id = "crm_core_organization_type",
 *   label = @Translation("CRM Core Organization type"),
 *   bundle_of = "crm_core_organization",
 *   config_prefix = "organization_type",
 *   handlers = {
 *     "access" = "Drupal\crm_core_contact\OrganizationTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\crm_core_contact\Form\OrganizationTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\crm_core_contact\OrganizationTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer organization types",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *     "locked",
 *     "primary_fields",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/crm-core/organization-types/add",
 *     "edit-form" = "/admin/structure/crm-core/organization-types/{crm_core_organization_type}",
 *     "delete-form" = "/admin/structure/crm-core/organization-types/{crm_core_organization_type}/delete",
 *   }
 * )
 */
class OrganizationType extends ConfigEntityBundleBase implements ContactTypeInterface, EntityDescriptionInterface, RevisionableEntityBundleInterface {

  /**
   * The machine-readable name of this type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of this type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this type.
   *
   * @var string
   */
  protected $description;

  /**
   * Whether or not this type is locked.
   *
   * A boolean indicating whether this type is locked or not, locked contact
   * type cannot be edited or disabled/deleted.
   *
   * @var bool
   */
  protected $locked;

  /**
   * Primary fields.
   *
   * An array of key-value pairs, where key is the primary field type and value
   * is real field name used for this type.
   *
   * @var array
   */
  protected $primary_fields;

  /**
   * Should new entities of this bundle have a new revision by default.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);

    // Ensure default values are set.
    $values += [
      'locked' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getNames() {
    $organization_types = OrganizationType::loadMultiple();
    $organization_types = array_map(function ($organization_type) {
      return $organization_type->label();
    }, $organization_types);
    return $organization_types;
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

  /**
   * Gets primary fields.
   *
   * @return array
   *   Primary fields array.
   */
  public function getPrimaryFields() {
    return $this->primary_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

}
