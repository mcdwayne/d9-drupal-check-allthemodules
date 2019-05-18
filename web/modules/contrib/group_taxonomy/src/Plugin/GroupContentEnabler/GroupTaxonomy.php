<?php

namespace Drupal\group_taxonomy\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Allows Taxonomy vocabulary to be added to groups.
 *
 * @GroupContentEnabler(
 *   id = "group_taxonomy",
 *   label = @Translation("Group taxonomy"),
 *   description = @Translation("Adds taxonomy vocabulary to groups."),
 *   entity_type_id = "taxonomy_vocabulary",
 * )
 */
class GroupTaxonomy extends GroupContentEnablerBase {
}
