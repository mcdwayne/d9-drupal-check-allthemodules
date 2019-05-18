<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_dissertation_institution' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_dissertation_institution",
 *   label = @Translation("Dissertation institution"),
 *   description = @Translation("Dissertation institution."),
 *   name = "citation_dissertation_institution",
 *   group = "google_scholar",
 *   weight = 10,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationDissertationInstitution extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
