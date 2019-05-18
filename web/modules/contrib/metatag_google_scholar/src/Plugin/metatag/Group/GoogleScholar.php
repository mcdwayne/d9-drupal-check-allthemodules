<?php

namespace Drupal\metatag_google_scholar\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The Google Scholar group.
 *
 * @MetatagGroup(
 *   id = "google_scholar",
 *   label = @Translation("Google Scholar"),
 *   description = @Translation("Meta tags for indexing scholarly articles in Google Scholar."),
 *   weight = 3
 * )
 */
class GoogleScholar extends GroupBase {
  // Inherits everything from Base.
}
