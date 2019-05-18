<?php

namespace Drupal\drupal_content_sync\Plugin\drupal_content_sync\entity_handler;

use Drupal\drupal_content_sync\Plugin\EntityHandlerBase;

/**
 * Class DefaultEntityHandler, providing a minimalistic implementation for any
 * entity type.
 *
 * @EntityHandler(
 *   id = "drupal_content_sync_default_entity_handler",
 *   label = @Translation("Default"),
 *   weight = 100
 * )
 *
 * @package Drupal\drupal_content_sync\Plugin\drupal_content_sync\entity_handler
 */
class DefaultEntityHandler extends EntityHandlerBase {

  /**
   * @inheritdoc
   */
  public static function supports($entity_type, $bundle) {
    $forbidden = [
      // Handling sensitive data like passwords via synchronization is not
      // supported by default. We suggest using LDAP or similar approaches
      // instead.
      'user',
      // These entities all have a separate default handler that handles
      // specific aspects in more detail than this general handler. So
      // we don't suggest using this general handler in that cases.
      'file',
      'media',
      'node',
      'menu_link_content',
      'taxonomy_term',
    ];
    return !in_array($entity_type, $forbidden);
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
