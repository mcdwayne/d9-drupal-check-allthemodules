<?php

namespace Drupal\metatag_cxense\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Facebook "fb:admins" meta tag.
 *
 * @MetatagTag(
 *   id = "cxenseparse_pageclass",
 *   label = @Translation("Cxsense Page Class"),
 *   description = @Translation("The class of a page."),
 *   name = "cXenseParse:pageclass",
 *   group = "cxense",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CxensePageclass extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
