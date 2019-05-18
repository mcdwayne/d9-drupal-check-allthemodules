<?php

namespace Drupal\protected_file\Plugin\media\Source;

use Drupal\media\Plugin\media\Source\File;

/**
 * Protected file entity media source.
 *
 * @see \Drupal\file\FileInterface
 *
 * @MediaSource(
 *   id = "protected_file",
 *   label = @Translation("Protected File"),
 *   description = @Translation("Use local files for reusable protected media."),
 *   allowed_field_types = {"protected_file"},
 *   default_thumbnail_filename = "generic.png"
 * )
 */
class ProtectedFile extends File {

}
