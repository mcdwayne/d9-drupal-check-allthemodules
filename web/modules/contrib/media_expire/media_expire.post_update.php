<?php

/**
 * @file
 * Install file.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Update fallback image configuration.
 */
function media_expire_post_update_fallback_id(&$sandbox = NULL) {
  $mediaStorage = \Drupal::entityTypeManager()->getStorage('media');
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'media_type', function ($bundle) use ($mediaStorage) {
    /** @var \Drupal\media\Entity\MediaType $bundle */
    $fallback_media = $bundle->getThirdPartySetting('media_expire', 'fallback_media');
    if (!empty($fallback_media)) {
      if ($media = $mediaStorage->load($fallback_media)) {
        $bundle->setThirdPartySetting('media_expire', 'fallback_media', $media->uuid());
        return TRUE;
      }
    }
  });
}
