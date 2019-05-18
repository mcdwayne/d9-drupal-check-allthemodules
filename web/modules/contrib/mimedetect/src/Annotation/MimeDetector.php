<?php

namespace Drupal\mimedetect\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MimeDetector annotation object.
 *
 * @see \Drupal\mimedetect\MimeDetectPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class MimeDetector extends Plugin {

  /**
   * A brief, human readable, description of the MIME detector.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The file name extensions on which this detector can act.
   *
   * @var array
   */
  public $filename_extensions = [];

}
