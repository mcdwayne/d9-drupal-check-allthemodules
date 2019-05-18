<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_lastpage' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_lastpage",
 *   label = @Translation("Last page"),
 *   description = @Translation("Last page."),
 *   name = "citation_lastpage",
 *   group = "google_scholar",
 *   weight = 9,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationLastpage extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
