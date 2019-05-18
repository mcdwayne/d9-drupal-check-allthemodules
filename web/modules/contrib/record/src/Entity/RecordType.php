<?php

namespace Drupal\record\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\record\RecordTypeInterface;

/**
 * Defines the Record type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "record_type",
 *   label = @Translation("Record type"),
 *   label_collection = @Translation("Record types"),
 *   label_singular = @Translation("record type"),
 *   label_plural = @Translation("record types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count record type",
 *     plural = "@count record types"
 *   ),
 *   handlers = {
 *     "access" = "Drupal\record\RecordTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\record\RecordTypeForm",
 *       "edit" = "Drupal\record\RecordTypeForm",
 *       "delete" = "Drupal\record\Form\RecordTypeDeleteConfirm",
 *       "properties" = "Drupal\record\Form\RecordTypePropertiesForm"
 *     },
 *     "list_builder" = "Drupal\record\RecordTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer record types",
 *   config_prefix = "record_type",
 *   bundle_of = "record",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/record/add",
 *     "edit-form" = "/admin/structure/record/{record_type}",
 *     "delete-form" = "/admin/structure/record/{record_type}/delete",
 *     "collection" = "/admin/structure/record",
 *   },
 * )
 */
class RecordType extends ConfigEntityBundleBase implements RecordTypeInterface {

  /**
   * The machine name of this record type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the record type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this record type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating a record of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * Whether record items should be published by default.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * Default value of the 'Create new revision' checkbox of this record type.
   *
   * @var bool
   */
  protected $new_revision = FALSE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
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
    return $this->set('description', $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('record.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function displaySubmitted() {
    return $this->display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplaySubmitted($display_submitted) {
    $this->display_submitted = $display_submitted;
  }

}
