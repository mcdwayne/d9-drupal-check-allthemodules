<?php

namespace Drupal\paragraphs_sets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\paragraphs_sets\ParagraphsSetInterface;

/**
 * Defines the ParagraphsSet entity.
 *
 * @ConfigEntityType(
 *   id = "paragraphs_set",
 *   label = @Translation("Paragraphs set"),
 *   config_prefix = "set",
 *   handlers = {
 *     "list_builder" = "Drupal\paragraphs_sets\Controller\ParagraphsSetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\paragraphs_sets\Form\ParagraphsSetForm",
 *       "edit" = "Drupal\paragraphs_sets\Form\ParagraphsSetForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer paragraphs sets",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "icon_uuid",
 *     "description",
 *     "paragraphs",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/paragraphs_set/{paragraphs_set}",
 *     "delete-form" = "/admin/structure/paragraphs_set/{paragraphs_set}/delete",
 *     "collection" = "/admin/structure/paragraphs_set",
 *   }
 * )
 */
class ParagraphsSet extends ConfigEntityBase implements ParagraphsSetInterface {

  /**
   * The ParagraphsSet ID.
   *
   * @var string
   */
  public $id;

  /**
   * The ParagraphsSet label.
   *
   * @var string
   */
  public $label;

  /**
   * UUID of the Paragraphs set icon file.
   *
   * @var string
   */
  protected $icon_uuid;

  /**
   * A brief description of this paragraph set.
   *
   * @var string
   */
  public $description;

  /**
   * List of paragraphs in this set.
   *
   * @var array
   */
  public $paragraphs;

  /**
   * {@inheritdoc}
   */
  public function getIconFile() {
    if ($this->icon_uuid && $icon = $this->getFileByUuid($this->icon_uuid)) {
      return $icon;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconUrl() {
    if ($image = $this->getIconFile()) {
      return file_create_url($image->getFileUri());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
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
  public function getParagraphs() {
    return $this->paragraphs;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Add the file icon entity as dependency if a UUID was specified.
    if ($this->icon_uuid && $file_icon = $this->getIconFile()) {
      $this->addDependency($file_icon->getConfigDependencyKey(), $file_icon->getConfigDependencyName());
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Update the file usage for the icon files.
    if (!$update || $this->icon_uuid != $this->original->icon_uuid) {
      // The icon has changed. Update file usage.
      /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
      $file_usage = \Drupal::service('file.usage');

      // Add usage of the new icon file, if it exists. It might not exist, if
      // this Paragraphs type was imported as configuration, or if the icon has
      // just been removed.
      if ($this->icon_uuid && $new_icon = $this->getFileByUuid($this->icon_uuid)) {
        $file_usage->add($new_icon, 'paragraphs_sets', 'paragraphs_set', $this->id());
      }
      if ($update) {
        // Delete usage of the old icon file, if it exists.
        if ($this->original->icon_uuid && $old_icon = $this->getFileByUuid($this->original->icon_uuid)) {
          $file_usage->delete($old_icon, 'paragraphs_sets', 'paragraphs_set', $this->id());
        }
      }
    }

    parent::postSave($storage, $update);
  }

  /**
   * Gets the file entity defined by the UUID.
   *
   * @param string $uuid
   *   The file entity's UUID.
   *
   * @return \Drupal\file\FileInterface|null
   *   The file entity. NULL if the UUID is invalid.
   */
  protected function getFileByUuid($uuid) {
    $files = $this->entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uuid' => $uuid]);
    if ($files) {
      return current($files);
    }

    return NULL;
  }

}
