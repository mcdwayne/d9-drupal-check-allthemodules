<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_pdf_url' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_pdf_url",
 *   label = @Translation("PDF URL"),
 *   description = @Translation("PDF URL."),
 *   name = "citation_pdf_url",
 *   group = "google_scholar",
 *   weight = 12,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class citationPdfUrl extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
