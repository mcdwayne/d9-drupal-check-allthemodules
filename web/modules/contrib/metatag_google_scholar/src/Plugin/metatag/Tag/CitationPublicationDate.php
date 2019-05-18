<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_publication_date' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_publication_date",
 *   label = @Translation("Publication date"),
 *   description = @Translation("The date of publication. Full dates in the 2010/5/12 format if available; or a year alone otherwise. This tag is required for inclusion in Google Scholar."),
 *   name = "citation_publication_date",
 *   group = "google_scholar",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationPublicationDate extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
