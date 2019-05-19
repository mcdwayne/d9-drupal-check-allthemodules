<?php

namespace Drupal\social_link_field\Plugin\SocialLinkField\Platform;

use Drupal\social_link_field\PlatformBase;

/**
 * Provides 'facebook' platform.
 *
 * @SocialLinkFieldPlatform(
 *   id = "facebook",
 *   name = @Translation("Facebook"),
 *   icon = "fa-facebook",
 *   iconSquare = "fa-facebook-square ",
 *   urlPrefix = "https://www.facebook.com/",
 * )
 */
class Facebook extends PlatformBase {}
