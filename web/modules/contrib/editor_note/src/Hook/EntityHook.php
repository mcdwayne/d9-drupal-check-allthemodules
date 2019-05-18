<?php

namespace Drupal\editor_note\Hook;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\editor_note\EditorNoteHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Be aware of some entity hooks to perform actions on editor note.
 */
class EntityHook implements ContainerInjectionInterface {

  /**
   * Editor Note helper service.
   *
   * @var \Drupal\editor_note\EditorNoteHelperService
   */
  protected $editorNoteHelper;

  /**
   * The entity reference selection handler plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\editor_note\EditorNoteHelperService $editor_note_helper
   *   Helper service object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityTypeManager object.
   */
  public function __construct(
    EditorNoteHelperService $editor_note_helper,
    EntityTypeManagerInterface $entity_type_manager) {
    $this->editorNoteHelper = $editor_note_helper;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('editor_note.helper'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Save Editor Note on entity insert.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @see editor_note_entity_insert
   */
  public function onEntityInsert(EntityInterface $entity) {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    $field_definitions = $entity->getFieldDefinitions();
    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_definition->getType() === 'editor_note_item') {
        $note_value = $entity->get($field_name)->value;
        if (empty($note_value)) {
          return;
        }
        // Create Editor Note entity.
        $this->editorNoteHelper->createNote($entity, $field_name, $note_value);
      }
    }
  }

  /**
   * Delete Editor Note on entity delete.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @see editor_note_entity_delete
   */
  public function onEntityDelete(EntityInterface $entity) {
    $notes = $this->entityTypeManager->getStorage('editor_note')->loadByProperties([
      'bundle' => $entity->bundle(),
      'entity_id' => $entity->id(),
    ]);
    foreach ($notes as $note) {
      $note->delete();
    }
  }

  /**
   * Delete Editor Note on user deletion.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @see editor_note_entity_user_delete
   */
  public function onUserDelete(EntityInterface $entity) {
    $notes = $this->entityTypeManager->getStorage('editor_note')->loadByProperties([
      'uid' => $entity->id(),
    ]);
    foreach ($notes as $note) {
      $note->delete();
    }
  }

  /**
   * Reassign Editor Note on user cancel.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object.
   *
   * @see editor_note_user_cancel
   */
  public function onUserCancel(AccountInterface $account) {
    $notes = $this->entityTypeManager->getStorage('editor_note')->loadByProperties([
      'uid' => $account->id(),
    ]);
    foreach ($notes as $note) {
      // Assign to anonymous user.
      $note->set('uid', 0);
      $note->save();
    }
  }

  /**
   * Delete on Editor Note revisions on entity revision delete.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @see editor_note_entity_revision_delete
   */
  public function onEntityRevisionDelete(EntityInterface $entity) {
    $entities = $this->entityTypeManager->getStorage('editor_note')->loadByProperties([
      'bundle' => $entity->bundle(),
      'entity_id' => $entity->id(),
      'revision_id' => $entity->getOriginalId(),
    ]);
    foreach ($entities as $entity) {
      $entity->delete();
    }
  }

}
