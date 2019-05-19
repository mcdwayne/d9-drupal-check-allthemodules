<?php

namespace Drupal\social_link_field\Plugin\SocialLinkField\Platform;

use Drupal\social_link_field\PlatformBase;

/**
 * Provides 'youtube' platform.
 *
 * @SocialLinkFieldPlatform(
 *   id = "youtube",
 *   name = @Translation("Youtube Channel"),
 *   icon = "fa-youtube",
 *   iconSquare = "fa-youtube-square",
 *   urlPrefix = "http://www.youtube.com/channel/",
 * )
 */
class Youtube extends PlatformBase {}
