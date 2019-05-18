<?php

namespace Drupal\onehub;

use Drupal\file\Entity\File;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\onehub\OneHubApi;

/**
 * Class OneHubFileManager.
 *
 * @package Drupal\onehub
 */
class OneHubFileManager extends OneHubApi {

  /**
   * [$entity description]
   * @var [type]
   */
  protected $entity;

  /**
   * [$entity description]
   * @var [type]
   */
  protected $db;

  /**
   * [$entity description]
   * @var [type]
   */
  protected $field;

  /**
   * [$entity description]
   * @var [type]
   */
  protected $file;

  /**
   * {@inheritdoc}
   */
  public function __construct($entity) {
    parent::__construct();
    $this->entity = $entity;
    $this->db = \Drupal::database();
  }

  /**
   * Processes the file on entity insert / update.
   */
  public function processFile() {
    // Grab our entity info
    $type = $this->entity->getEntityType()->id();
    $bundle = $this->entity->bundle();

    // Get out of here if we are on a non-fieldable entity.
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $entity_type = $entityTypeManager->getDefinition($type);
    $class = $entity_type->getClass();
    if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      return;
    }

    // Load the fields from that entity.
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions($type, $bundle);

    // Get out of here if we are on a no fields on the entity.
    if (!isset($fields)) {
      return;
    }

    // Clear out the schema before adding new stuff.
    $this->clearSchema();

    // Check the fields and grab the onehub stuffs.
    foreach ($fields as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if ($field_definition->getType() == 'onehub') {
          $field = $this->entity->get($field_name)->getValue();
          foreach ($field as $f) {

            $this->field = $f;
            $this->file = $this->createFile($f['workspace'], $f['folder'], $f['target_id']);

            if ($this->file !== NULL) {
              $this->addFileToSchema();
            }
          }
        }
      }
    }
  }

  /**
   * Clears out out schema so wre don't have issues.
   */
  protected function clearSchema() {
    $this->db->delete('onehub')
      ->condition('entity_id', $this->entity->id())
      ->execute();
  }

  /**
   * Adds our files to our schema.
   */
  protected function addFileToSchema() {
    $timestamp = new \DateTime($this->file['file']['updated_at']);
    $workspace_id = $this->file['file']['workspace_id'];
    $folder_id = end($this->file['file']['ancestor_ids']);
    $ws_name = $this->getWorkspace($workspace_id);
    $f_name = $this->getFolder($workspace_id, $folder_id);

    $fields = [
      'oid' => $this->file['file']['id'],
      'entity_id' => $this->entity->id(),
      'workspace' => $workspace_id,
      'workspace_name' => isset($ws_name['name']) ? $ws_name['name'] : '',
      'folder' => $folder_id,
      'folder_name' => isset($f_name['filename']) ? $f_name['filename'] : '',
      'filename' => $this->file['file']['filename'],
      'timestamp' => $timestamp->getTimestamp(),
      'original_fid' => $this->field['target_id'],
    ];

    $this->db->insert('onehub')
      ->fields($fields)
      ->execute();

    file_delete($this->field['target_id']);
  }


}
