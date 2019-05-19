<?php

namespace Drupal\avatars_tinygraphs\Plugin\Avatars\Service;

use dpi\ak\AvatarIdentifierInterface;
use dpi\ak_tinygraphs\TinygraphsAvatarConfiguration;
use dpi\ak\AvatarConfigurationInterface;
use Drupal\avatars\Plugin\Avatars\Service\AvatarKitCommonService;

/**
 * Tinygraphs plugin.
 */
class Tinygraphs extends AvatarKitCommonService {

  /**
   * {@inheritdoc}
   */
  protected function newAvatarConfiguration(): AvatarConfigurationInterface {
    return (new TinygraphsAvatarConfiguration())
      ->setTheme('frogideas');
  }

  /**
   * {@inheritdoc}
   */
  public function createIdentifier() : AvatarIdentifierInterface {
    return (parent::createIdentifier())
      ->setHasher(function ($string) { return $string; });
  }

}
