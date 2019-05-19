<?php

namespace Drupal\social_link_field\Plugin\SocialLinkField\Platform;

use Drupal\social_link_field\PlatformBase;

/**
 * Provides 'github' platform.
 *
 * @SocialLinkFieldPlatform(
 *   id = "github",
 *   name = @Translation("GitHub"),
 *   icon = "fa-github",
 *   iconSquare = "fa-github-square",
 *   urlPrefix = "https://github.com/",
 * )
 */
class GitHub extends PlatformBase {}
