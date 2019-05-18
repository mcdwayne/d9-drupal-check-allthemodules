<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_isbn' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_isbn",
 *   label = @Translation("ISBN"),
 *   description = @Translation("ISBN."),
 *   name = "citation_isbn",
 *   group = "google_scholar",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationIsbn extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
