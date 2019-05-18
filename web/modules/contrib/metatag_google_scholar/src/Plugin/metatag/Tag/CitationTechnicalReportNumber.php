<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_technical_report_number' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_technical_report_number",
 *   label = @Translation("Technical report number"),
 *   description = @Translation("Technical report number."),
 *   name = "citation_technical_report_number",
 *   group = "google_scholar",
 *   weight = 11,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationTechnicalReportNumber extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
