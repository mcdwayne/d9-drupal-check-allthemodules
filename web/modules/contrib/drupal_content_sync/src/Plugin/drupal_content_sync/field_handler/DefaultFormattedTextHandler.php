<?php

namespace Drupal\drupal_content_sync\Plugin\drupal_content_sync\field_handler;

use Drupal\drupal_content_sync\ImportIntent;
use Drupal\drupal_content_sync\Plugin\FieldHandlerBase;
use Drupal\drupal_content_sync\SyncIntent;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Providing a minimalistic implementation for any field type.
 *
 * @FieldHandler(
 *   id = "drupal_content_sync_default_formatted_text_handler",
 *   label = @Translation("Default Formatted Text"),
 *   weight = 90
 * )
 *
 * @package Drupal\drupal_content_sync\Plugin\drupal_content_sync\field_handler
 */
class DefaultFormattedTextHandler extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public static function supports($entity_type, $bundle, $field_name, FieldDefinitionInterface $field) {
    $allowed = ["text_with_summary", "text_long"];
    return in_array($field->getType(), $allowed) !== FALSE;
  }

  /**
   * Replace all "/node/..." links with their correct ID for the current site.
   *
   * @TODO If a new entity is added, we should scan the database for existing
   * references to it that can now be resolved.
   *
   * @param $text
   *
   * @return string
   */
  protected function replaceEntityReferenceLinks($text) {
    $entity_repository = \Drupal::service('entity.repository');

    return preg_replace_callback(
      '@data-entity-uuid="([0-9a-z-]+)" href="/node/([0-9]+)"@',
      function ($matches) use ($entity_repository) {
        $uuid = $matches[1];
        $id   = $matches[2];

        try {
          $node = $entity_repository->loadEntityByUuid('node', $uuid);
          if ($node) {
            $id = $node->id();
          }
        }
        catch (\Exception $e) {
        }

        return 'data-entity-uuid="' . $uuid . '" href="/node/' . $id . '"';
      },
      $text
    );
  }

  /**
   * @inheritdoc
   */
  public function import(ImportIntent $intent) {
    $action = $intent->getAction();
    /**
     * @var \Drupal\Core\Entity\FieldableEntityInterface $entity
     */
    $entity = $intent->getEntity();

    // Deletion doesn't require any action on field basis for static data.
    if ($action == SyncIntent::ACTION_DELETE) {
      return FALSE;
    }

    if ($intent->shouldMergeChanges()) {
      return FALSE;
    }

    $data = $intent->getField($this->fieldName);

    if (empty($data)) {
      $entity->set($this->fieldName, NULL);
    }
    else {
      $result = [];

      foreach ($data as $item) {
        if (!empty($item['value'])) {
          // Replace node links correctly.
          $item['value'] = $this->replaceEntityReferenceLinks($item['value']);
        }
        $result[] = $item;
      }

      $entity->set($this->fieldName, $result);
    }

    return TRUE;
  }

}
