<?php

namespace Drupal\bibcite_entity\Plugin\Action;

/**
 * Delete reference action.
 *
 * @Action(
 *   id = "bibcite_entity_reference_delete",
 *   label = @Translation("Delete references"),
 *   type = "bibcite_reference",
 *   confirm_form_route_name = "entity.bibcite_reference.delete_multiple_form",
 * )
 */
class ReferenceDelete extends EntityDeleteBase {
}
