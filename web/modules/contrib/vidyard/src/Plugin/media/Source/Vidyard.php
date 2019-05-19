<?php

namespace Drupal\vidyard\Plugin\media\Source;

use Drupal\media\Plugin\media\Source\OEmbed;

/**
 * Media source for Vidyard videos.
 *
 * @MediaSource(
 *   id = "vidyard",
 *   label = @Translation("Vidyard video"),
 *   description = @Translation("Use a video from Vidyard video platform."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "video.png",
 *   providers = {"Vidyard"},
 * )
 */
class Vidyard extends OEmbed {
  // No need for anything in here; the base plugin can take care of typical interactions
  // with external oEmbed services.
}
