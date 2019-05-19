<?php

namespace Drupal\social_link_field\Plugin\SocialLinkField\Platform;

use Drupal\social_link_field\PlatformBase;

/**
 * Provides 'pinterest' platform.
 *
 * @SocialLinkFieldPlatform(
 *   id = "pinterest",
 *   name = @Translation("Pinterest"),
 *   icon = "fa-pinterest-p",
 *   iconSquare = "fa-pinterest-square",
 *   urlPrefix = "http://www.pinterest.com/",
 * )
 */
class Pinterest extends PlatformBase {}
