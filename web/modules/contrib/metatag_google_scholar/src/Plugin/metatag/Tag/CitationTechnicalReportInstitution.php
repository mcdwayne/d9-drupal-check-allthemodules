<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_technical_report_institution' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_technical_report_institution",
 *   label = @Translation("Technical report institution"),
 *   description = @Translation("Technical report institution."),
 *   name = "citation_technical_report_institution",
 *   group = "google_scholar",
 *   weight = 10,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationTechnicalReportInstitution extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
