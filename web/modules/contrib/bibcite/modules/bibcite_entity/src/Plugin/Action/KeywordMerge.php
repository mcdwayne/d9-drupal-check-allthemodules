<?php

namespace Drupal\bibcite_entity\Plugin\Action;

/**
 * Merge keyword action.
 *
 * @Action(
 *   id = "bibcite_entity_keyword_merge",
 *   label = @Translation("Merge keyword"),
 *   type = "bibcite_keyword",
 *   confirm_form_route_name = "entity.bibcite_keyword.bibcite_merge_multiple_form",
 * )
 */
class KeywordMerge extends EntityMergeBase {

}
