<?php

namespace Drupal\social_link_field\Plugin\SocialLinkField\Platform;

use Drupal\social_link_field\PlatformBase;

/**
 * Provides 'vimeo' platform.
 *
 * @SocialLinkFieldPlatform(
 *   id = "vimeo",
 *   name = @Translation("Vimeo"),
 *   icon = "fa-vimeo",
 *   iconSquare = "vimeo-square",
 *   urlPrefix = "http://www.vimeo.com/",
 * )
 */
class Vimeo extends PlatformBase {}
