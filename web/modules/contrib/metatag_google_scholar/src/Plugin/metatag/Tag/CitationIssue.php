<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_issue' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_issue",
 *   label = @Translation("Issue"),
 *   description = @Translation("Issue."),
 *   name = "citation_issue",
 *   group = "google_scholar",
 *   weight = 7,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationIssue extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
