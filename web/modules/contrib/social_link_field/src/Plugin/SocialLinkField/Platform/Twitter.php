<?php

namespace Drupal\social_link_field\Plugin\SocialLinkField\Platform;

use Drupal\social_link_field\PlatformBase;

/**
 * Provides 'twitter' platform.
 *
 * @SocialLinkFieldPlatform(
 *   id = "twitter",
 *   name = @Translation("Twitter"),
 *   icon = "fa-twitter",
 *   iconSquare = "fa-twitter-square",
 *   urlPrefix = "https://www.twitter.com/",
 * )
 */
class Twitter extends PlatformBase {}
