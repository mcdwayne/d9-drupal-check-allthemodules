<?php

namespace Drupal\box\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Box type entity.
 *
 * @ConfigEntityType(
 *   id = "box_type",
 *   label = @Translation("Box type"),
 *   label_collection = @Translation("Box types"),
 *   label_singular = @Translation("box type"),
 *   label_plural = @Translation("box types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count box type",
 *     plural = "@count box types"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\box\BoxTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\box\Form\BoxTypeForm",
 *       "edit" = "Drupal\box\Form\BoxTypeForm",
 *       "delete" = "Drupal\box\Form\BoxTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\box\BoxTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "box_type",
 *   admin_permission = "administer box entities",
 *   bundle_of = "box",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/box-types/add",
 *     "edit-form" = "/admin/structure/box-types/manage/{box_type}",
 *     "delete-form" = "/admin/structure/box-types/manage/{box_type}/delete",
 *     "collection" = "/admin/structure/box-types"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *     "new_revision",
 *     "require_revision_log"
 *   }
 * )
 */
class BoxType extends ConfigEntityBundleBase implements BoxTypeInterface {

  /**
   * The Box type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Box type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this Box type.
   *
   * @var string
   */
  protected $description;

  /**
   * Default value of the 'Create new revision' checkbox of this box type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * Default value of the 'Require log message' checkbox of this box type.
   *
   * @var bool
   */
  protected $require_revision_log = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
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
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionLogRequired() {
    return $this->require_revision_log;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLogRequired() {
    $this->require_revision_log = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLogOptional() {
    $this->require_revision_log = FALSE;
  }

}
