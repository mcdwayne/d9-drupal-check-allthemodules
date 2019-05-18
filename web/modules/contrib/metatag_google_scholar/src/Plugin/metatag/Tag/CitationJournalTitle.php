<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_journal_title' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_journal_title",
 *   label = @Translation("Journal title"),
 *   description = @Translation("Journal title."),
 *   name = "citation_journal_title",
 *   group = "google_scholar",
 *   weight = 3,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CitationJournalTitle extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
