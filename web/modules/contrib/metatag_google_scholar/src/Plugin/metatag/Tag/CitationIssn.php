<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_issn' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_issn",
 *   label = @Translation("ISSN"),
 *   description = @Translation("ISSN."),
 *   name = "citation_issn",
 *   group = "google_scholar",
 *   weight = 4,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationIssn extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
