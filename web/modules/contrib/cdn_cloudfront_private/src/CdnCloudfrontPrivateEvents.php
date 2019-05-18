<?php

namespace Drupal\cdn_cloudfront_private;

/**
 * Class CdnCloudfrontPrivateEvents.
 *
 * @package Drupal\cdn_cloudfront_private
 */
final class CdnCloudfrontPrivateEvents {

  /**
   * Event for determining the protection status of a uri using Cloudfront.
   */
  const DETERMINE_URI_PROTECTION = 'cdn_cloudfront_private.determine_protection';

}
