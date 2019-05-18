<?php

namespace Drupal\entity_generic\Entity;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the entity type class.
 */
abstract class GenericType extends GenericConfig implements GenericTypeInterface {

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
  public function isLocked() {
    $locked = \Drupal::state()->get($this->entityTypeManager()->getDefinition($this->getEntityTypeId())->getBundleOf() . '.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update && $this->getOriginalId() != $this->id()) {
      $update_count = \Drupal::entityTypeManager()->getStorage($this->getOriginalId())->updateType($this->getOriginalId(), $this->id());
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

}
