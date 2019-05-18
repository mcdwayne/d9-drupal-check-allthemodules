<?php

namespace Drupal\lightning_media_video_file\Plugin\media\Source;

use Drupal\lightning_media\FileInputExtensionMatchTrait;
use Drupal\lightning_media\InputMatchInterface;
use Drupal\media\Plugin\media\Source\VideoFile as BaseVideo;

/**
 * Input-matching version of the Video Entity media source.
 */
class VideoFile extends BaseVideo implements InputMatchInterface {

  use FileInputExtensionMatchTrait;

}
