<?php

namespace Drupal\cxenserecs\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * Cxense Recommendations.
 *
 * @MetatagGroup(
 *   id = "cxenserecs",
 *   label = @Translation("Cxense Recommendations"),
 *   description = @Translation("Provides support for Cxense's custom meta tags."),
 *   weight = 4
 * )
 */
class CxenseRecs extends GroupBase {
  // Inherits everything from Base.
}
