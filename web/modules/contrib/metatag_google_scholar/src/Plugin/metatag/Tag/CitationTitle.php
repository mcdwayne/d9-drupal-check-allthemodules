<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_title' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_title",
 *   label = @Translation("Title"),
 *   description = @Translation("Title of the paper. Required for inclusion in Google Scholar."),
 *   name = "citation_title",
 *   group = "google_scholar",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationTitle extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
