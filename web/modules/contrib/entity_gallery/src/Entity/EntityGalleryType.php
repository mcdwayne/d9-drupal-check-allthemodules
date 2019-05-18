<?php

namespace Drupal\entity_gallery\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\entity_gallery\EntityGalleryTypeInterface;

/**
 * Defines the Entity Gallery type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "entity_gallery_type",
 *   label = @Translation("Entity gallery type"),
 *   handlers = {
 *     "access" = "Drupal\entity_gallery\EntityGalleryTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\entity_gallery\EntityGalleryTypeForm",
 *       "edit" = "Drupal\entity_gallery\EntityGalleryTypeForm",
 *       "delete" = "Drupal\entity_gallery\Form\EntityGalleryTypeDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\entity_gallery\EntityGalleryTypeListBuilder",
 *   },
 *   admin_permission = "administer entity gallery types",
 *   config_prefix = "type",
 *   bundle_of = "entity_gallery",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/gallery-types/manage/{entity_gallery_type}",
 *     "delete-form" = "/admin/structure/gallery-types/manage/{entity_gallery_type}/delete",
 *     "collection" = "/admin/structure/gallery-types",
 *   },
 *   config_export = {
 *     "name",
 *     "type",
 *     "description",
 *     "gallery_type",
 *     "gallery_type_bundles",
 *     "help",
 *     "new_revision",
 *     "preview_mode",
 *     "display_submitted",
 *   }
 * )
 */
class EntityGalleryType extends ConfigEntityBundleBase implements EntityGalleryTypeInterface {

  /**
   * The machine name of this entity gallery type.
   *
   * @var string
   *
   * @todo Rename to $id.
   */
  protected $type;

  /**
   * The human-readable name of the entity gallery type.
   *
   * @var string
   *
   * @todo Rename to $label.
   */
  protected $name;

  /**
   * A brief description of this entity gallery type.
   *
   * @var string
   */
  protected $description;

  /**
   * The type of entity referenced by this entity gallery type.
   *
   * @var string
   */
  protected $gallery_type;

  /**
   * The bundles of the entity referenced by this entity gallery type.
   *
   * @var array
   */
  protected $gallery_type_bundles = [];

  /**
   * Help information shown to the user when creating an Entity Gallery of this
   * type.
   *
   * @var string
   */
  protected $help;

  /**
   * Default value of the 'Create new revision' checkbox of this entity gallery
   * type.
   *
   * @var bool
   */
  protected $new_revision = FALSE;

  /**
   * The preview mode.
   *
   * @var int
   */
  protected $preview_mode = DRUPAL_OPTIONAL;

  /**
   * Display setting for author and date Submitted by post information.
   *
   * @var bool
   */
  protected $display_submitted = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('entity_gallery.type.locked');
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
  public function displaySubmitted() {
    return $this->display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplaySubmitted($display_submitted) {
    $this->display_submitted = $display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewMode() {
    return $this->preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviewMode($preview_mode) {
    $this->preview_mode = $preview_mode;
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
  public function getGalleryType() {
    return $this->gallery_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getGalleryTypeBundles() {
    return $this->gallery_type_bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update && $this->getOriginalId() != $this->id()) {
      $update_count = entity_gallery_type_update_entity_galleries($this->getOriginalId(), $this->id());
      if ($update_count) {
        drupal_set_message(\Drupal::translation()->formatPlural($update_count,
          'Changed the entity gallery type of 1 post from %old-type to %type.',
          'Changed the entity gallery type of @count posts from %old-type to %type.',
          array(
            '%old-type' => $this->getOriginalId(),
            '%type' => $this->id(),
          )));
      }
    }
    if ($update) {
      // Clear the cached field definitions as some settings affect the field
      // definitions.
      $this->entityManager()->clearCachedFieldDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the entity gallery type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

}
