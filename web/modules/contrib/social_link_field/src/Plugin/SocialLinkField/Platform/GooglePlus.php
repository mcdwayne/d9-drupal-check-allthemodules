<?php

namespace Drupal\social_link_field\Plugin\SocialLinkField\Platform;

use Drupal\social_link_field\PlatformBase;

/**
 * Provides 'GooglePlus' platform.
 *
 * @SocialLinkFieldPlatform(
 *   id = "googleplus",
 *   name = @Translation("Google+"),
 *   icon = "fa-google-plus",
 *   iconSquare = "fa-google-plus-square",
 *   urlPrefix = "https://plus.google.com/",
 * )
 */
class GooglePlus extends PlatformBase {}
