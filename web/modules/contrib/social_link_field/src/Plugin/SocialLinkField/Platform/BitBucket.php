<?php

namespace Drupal\social_link_field\Plugin\SocialLinkField\Platform;

use Drupal\social_link_field\PlatformBase;

/**
 * Provides 'BitBucket' platform.
 *
 * @SocialLinkFieldPlatform(
 *   id = "bitbucket",
 *   name = @Translation("BitBucket"),
 *   icon = "fa-bitbucket",
 *   iconSquare = "fa-bitbucket-square",
 *   urlPrefix = "https://bitbucket.org/",
 * )
 */
class BitBucket extends PlatformBase {}
