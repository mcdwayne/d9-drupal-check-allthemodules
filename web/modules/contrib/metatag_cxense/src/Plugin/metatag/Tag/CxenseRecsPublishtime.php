<?php

namespace Drupal\metatag_cxense\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Facebook "fb:admins" meta tag.
 *
 * @MetatagTag(
 *   id = "cxenseparse_recs_publishtime",
 *   label = @Translation("Cxsense Publish Time"),
 *   description = @Translation("The date/time of the publication for this page. Must be in the following format: Y-m-d\TH:i:s\Z."),
 *   name = "cXenseParse:recs:publishtime",
 *   group = "cxense",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CxenseRecsPublishtime extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
