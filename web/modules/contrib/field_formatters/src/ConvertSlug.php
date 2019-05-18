<?php

namespace Drupal\field_formatters;

use Cocur\Slugify\Slugify;

/**
 * Define the Slug Class
 */
class ConvertSlug implements ConvertSlugInterface {

   /**
   * The slugify object.
   */
  protected $obj_slugify;

   /**
   * Create an instance of Slugify.
   */
  public function __construct() {
    $this->obj_slugify = new Slugify();
  }

   /**
   * {@inheritdoc}
   */
  public function textIntoSlugSeparator($string, $separator) {
    return $this->obj_slugify->slugify($string, $separator);
  }

}





