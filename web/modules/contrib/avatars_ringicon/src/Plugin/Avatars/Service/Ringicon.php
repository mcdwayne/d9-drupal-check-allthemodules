<?php

namespace Drupal\avatars_ringicon\Plugin\Avatars\Service;

use dpi\ak\AvatarIdentifierInterface;
use Drupal\avatars\Plugin\Avatars\Service\AvatarKitCommonService;

/**
 * Ringicon plugin.
 */
class Ringicon extends AvatarKitCommonService {

  /**
   * {@inheritdoc}
   */
  public function getAvatar(AvatarIdentifierInterface $identifier): ?string {
    $uri = parent::getAvatar($identifier);
    if ($uri) {
      // @todo rework this, let ak handle creating a file same as remote.
      // file_save_data does not give it proper extension + directory.
      $data = file_get_contents($uri);
      // @todo Mime and filename need to be set.
      $file = file_save_data($data);
      return $file->getFileUri();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createIdentifier() : AvatarIdentifierInterface {
    return (parent::createIdentifier())
      ->setHasher(function ($string) {
        return $string;
      });
  }

}
