<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_author' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_author",
 *   label = @Translation("Author"),
 *   description = @Translation("Authors of the paper. At least one author tag is required for inclusion in Google Scholar."),
 *   name = "citation_author",
 *   group = "google_scholar",
 *   weight = 1,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class CitationAuthor extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
