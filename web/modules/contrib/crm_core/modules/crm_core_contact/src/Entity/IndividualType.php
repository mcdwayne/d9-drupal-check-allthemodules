<?php

namespace Drupal\crm_core_contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\crm_core_contact\ContactTypeInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * CRM Individual Type Entity Class.
 *
 * @ConfigEntityType(
 *   id = "crm_core_individual_type",
 *   label = @Translation("CRM Core Individual type"),
 *   bundle_of = "crm_core_individual",
 *   config_prefix = "type",
 *   handlers = {
 *     "access" = "Drupal\crm_core_contact\IndividualTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\crm_core_contact\Form\IndividualTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\crm_core_contact\ContactTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer individual types",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name",
 *   },
 *   config_export = {
 *     "name",
 *     "type",
 *     "description",
 *     "locked",
 *     "primary_fields",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/crm-core/individual-types/add",
 *     "edit-form" = "/admin/structure/crm-core/individual-types/{crm_core_individual_type}",
 *     "delete-form" = "/admin/structure/crm-core/individual-types/{crm_core_individual_type}/delete",
 *   }
 * )
 */
class IndividualType extends ConfigEntityBundleBase implements ContactTypeInterface, EntityDescriptionInterface, RevisionableEntityBundleInterface {

  /**
   * The machine-readable name of this type.
   *
   * @var string
   */
  public $type;

  /**
   * The human-readable name of this type.
   *
   * @var string
   */
  public $name;

  /**
   * A brief description of this type.
   *
   * @var string
   */
  public $description;

  /**
   * Whether or not this type is locked.
   *
   * A boolean indicating whether this type is locked or not, locked individual
   * type cannot be edited or disabled/deleted.
   *
   * @var bool
   */
  public $locked;

  /**
   * Primary fields.
   *
   * An array of key-value pairs, where key is the primary field type and value
   * is real field name used for this type.
   *
   * @var array
   */
  public $primary_fields;

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
    return $this->type;
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
    $individual_types = IndividualType::loadMultiple();
    $individual_types = array_map(function ($individual_type) {
      return $individual_type->label();
    }, $individual_types);
    return $individual_types;
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
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

}
