<?php

namespace Drupal\bibcite_entity\Plugin\Action;

/**
 * Merge contributor action.
 *
 * @Action(
 *   id = "bibcite_entity_contributor_merge",
 *   label = @Translation("Merge contributor"),
 *   type = "bibcite_contributor",
 *   confirm_form_route_name = "entity.bibcite_contributor.bibcite_merge_multiple_form",
 * )
 */
class ContributorMerge extends EntityMergeBase {
}
