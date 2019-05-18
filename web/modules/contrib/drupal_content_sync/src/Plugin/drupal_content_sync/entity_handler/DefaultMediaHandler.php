<?php

namespace Drupal\drupal_content_sync\Plugin\drupal_content_sync\entity_handler;

use Drupal\drupal_content_sync\Plugin\EntityHandlerBase;

/**
 * Class DefaultMediaHandler, providing a minimalistic implementation for the
 * media entity type.
 *
 * @EntityHandler(
 *   id = "drupal_content_sync_media_entity_handler",
 *   label = @Translation("Default Media"),
 *   weight = 90
 * )
 *
 * @package Drupal\drupal_content_sync\Plugin\drupal_content_sync\entity_handler
 */
class DefaultMediaHandler extends EntityHandlerBase {

  /**
   * @inheritdoc
   */
  public static function supports($entity_type, $bundle) {
    return $entity_type == 'media';
  }

  /**
   * @inheritdoc
   */
  public function getForbiddenFields() {
    return array_merge(
      parent::getForbiddenFields(),
      [
        // Must be recreated automatically on remote site.
        'thumbnail',
      ]
    );
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

}
