<?php

/**
 * @file
 * Contains \Drupal\entity_base\Entity\EntityBaseType.
 */


namespace Drupal\entity_base\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the entity type class.
 */
abstract class EntityBaseType extends ConfigEntityBundleBase implements EntityBaseTypeInterface {

  /**
   * The machine name of this entity type.
   *
   * @var string
   */
  protected $id;

  /**
   * The universally unique identifier of the entity type.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The human-readable name of the entity type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this entity type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating an entity of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * Default value of the 'Create new revision' checkbox of this entity type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * The weight of the entity type compared to others.
   *
   * @var integer
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->get('label');
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get($this->entityTypeManager()->getDefinition($this->getEntityTypeId())->getBundleOf() . '.type.locked');
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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update && $this->getOriginalId() != $this->id()) {
      // @todo mvv change entity_base to specific entity type
      $update_count = \Drupal::entityTypeManager()->getStorage('entity_base')->updateType($this->getOriginalId(), $this->id());
      if ($update_count) {
        drupal_set_message(\Drupal::translation()->formatPlural($update_count,
          'Changed the type of 1 object from %old-type to %type.',
          'Changed the type of @count objects from %old-type to %type.',
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

    // Clear the entity type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

}
