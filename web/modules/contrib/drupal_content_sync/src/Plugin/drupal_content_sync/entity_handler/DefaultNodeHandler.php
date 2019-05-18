<?php

namespace Drupal\drupal_content_sync\Plugin\drupal_content_sync\entity_handler;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\drupal_content_sync\ExportIntent;
use Drupal\drupal_content_sync\ImportIntent;
use Drupal\drupal_content_sync\SyncIntent;
use Drupal\drupal_content_sync\Plugin\EntityHandlerBase;

/**
 * Class DefaultNodeHandler, providing proper handling for published/unpublished
 * content.
 *
 * @EntityHandler(
 *   id = "drupal_content_sync_default_node_handler",
 *   label = @Translation("Default Node"),
 *   weight = 90
 * )
 *
 * @package Drupal\drupal_content_sync\Plugin\drupal_content_sync\entity_handler
 */
class DefaultNodeHandler extends EntityHandlerBase {

  /**
   * @inheritdoc
   */
  public static function supports($entity_type, $bundle) {
    return $entity_type == 'node';
  }

  /**
   * @inheritdoc
   */
  public function getAllowedExportOptions() {
    return [
      ExportIntent::EXPORT_DISABLED,
      ExportIntent::EXPORT_AUTOMATICALLY,
      ExportIntent::EXPORT_AS_DEPENDENCY,
      ExportIntent::EXPORT_MANUALLY,
    ];
  }

  /**
   * @inheritdoc
   */
  public function export(ExportIntent $intent, FieldableEntityInterface $entity = NULL) {
    if (!parent::export($intent, $entity)) {
      return FALSE;
    }

    if (!$entity) {
      $entity = $intent->getEntity();
    }

    /**
     * @var \Drupal\node\NodeInterface $entity
     */
    $intent->setField('created', intval($entity->getCreatedTime()));
    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function getAllowedPreviewOptions() {
    return [
      'table' => 'Table',
      'preview_mode' => 'Preview mode',
    ];
  }

  /**
   * @inheritdoc
   */
  public function getHandlerSettings() {
    return [
      // TODO Move to default handler for all entities that can be published.
      'ignore_unpublished' => [
        '#type' => 'checkbox',
        '#title' => 'Ignore unpublished content',
        '#default_value' => isset($this->settings['handler_settings']['ignore_unpublished']) && $this->settings['handler_settings']['ignore_unpublished'] === 0 ? 0 : 1,
      ],
    ];
  }

  /**
   * @inheritdoc
   */
  public function ignoreImport(ImportIntent $intent) {
    // Not published? Ignore this revision then.
    if (empty($intent->getField('status')) && $this->settings['handler_settings']['ignore_unpublished']) {
      // Unless it's a delete, then it won't have a status and is independent
      // of published state, so we don't ignore the import.
      if ($intent->getAction() != SyncIntent::ACTION_DELETE) {
        return TRUE;
      }
    }

    return parent::ignoreImport($intent);
  }

  /**
   * @inheritdoc
   */
  public function ignoreExport(ExportIntent $intent) {
    /**
     * @var \Drupal\node\NodeInterface $entity
     */
    $entity = $intent->getEntity();

    if (!$entity->isPublished() && $this->settings['handler_settings']['ignore_unpublished']) {
      return TRUE;
    }

    return parent::ignoreExport($intent);
  }

}
