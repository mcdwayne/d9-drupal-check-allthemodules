<?php

namespace Drupal\mediaelement\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileAudioFormatter;

/**
 * Plugin implementation of the 'mediaelement_file_audio' formatter.
 *
 * @FieldFormatter(
 *   id = "mediaelement_file_audio",
 *   label = @Translation("MediaElement.js Audio"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class MediaElementAudioFieldFormatter extends FileAudioFormatter {

  // Include trait with global MediaElement formatter config items.
  use MediaElementFieldFormatterTrait;

}
