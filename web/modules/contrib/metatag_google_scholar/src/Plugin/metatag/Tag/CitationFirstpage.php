<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_firstpage' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_firstpage",
 *   label = @Translation("First page"),
 *   description = @Translation("First page."),
 *   name = "citation_firstpage",
 *   group = "google_scholar",
 *   weight = 8,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationFirstpage extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
