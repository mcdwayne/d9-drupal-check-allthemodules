<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_volume' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_volume",
 *   label = @Translation("Volume"),
 *   description = @Translation("Volume."),
 *   name = "citation_volume",
 *   group = "google_scholar",
 *   weight = 6,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationVolume extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
