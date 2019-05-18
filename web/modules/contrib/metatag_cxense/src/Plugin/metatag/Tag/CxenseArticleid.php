<?php

namespace Drupal\metatag_cxense\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Facebook "fb:admins" meta tag.
 *
 * @MetatagTag(
 *   id = "cxenseparse_articleid",
 *   label = @Translation("Cxsense Article ID"),
 *   description = @Translation("Unique ID of the article."),
 *   name = "cXenseParse:articleid",
 *   group = "cxense",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CxenseArticleid extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
