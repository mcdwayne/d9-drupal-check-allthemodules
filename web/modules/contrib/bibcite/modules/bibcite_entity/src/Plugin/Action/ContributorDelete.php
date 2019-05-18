<?php

namespace Drupal\bibcite_entity\Plugin\Action;

/**
 * Delete contributor action.
 *
 * @Action(
 *   id = "bibcite_entity_contributor_delete",
 *   label = @Translation("Delete contributors"),
 *   type = "bibcite_contributor",
 *   confirm_form_route_name = "entity.bibcite_contributor.delete_multiple_form",
 * )
 */
class ContributorDelete extends EntityDeleteBase {
}
