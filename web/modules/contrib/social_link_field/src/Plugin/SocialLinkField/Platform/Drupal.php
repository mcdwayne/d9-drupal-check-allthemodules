<?php

namespace Drupal\social_link_field\Plugin\SocialLinkField\Platform;

use Drupal\social_link_field\PlatformBase;

/**
 * Provides 'drupal' platform.
 *
 * @SocialLinkFieldPlatform(
 *   id = "drupal",
 *   name = @Translation("Drupal"),
 *   icon = "fa-drupal",
 *   urlPrefix = "https://www.drupal.org/u/",
 * )
 */
class Drupal extends PlatformBase {}
