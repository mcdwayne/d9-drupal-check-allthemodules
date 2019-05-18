<?php

namespace Drupal\libraries_provider\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an annotation for LibrarySource plugins.
 *
 * @Annotation
 */
class LibrarySource extends Plugin {

  /**
   * The annotated class ID.
   *
   * @var string
   */
  public $id;

}
