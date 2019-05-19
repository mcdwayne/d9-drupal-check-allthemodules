<?php

namespace Drupal\taxonomy_moderator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "status",
 *   label = @Translation("status", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class TaxonomyModeratorFieldConstraint extends Constraint {

  public $termDuplicate = '<strong>%value</strong> tag is already available.';

  public $termExist = '<strong>%value</strong> tag already exist. You can add directly at "Ketworks & Tags".';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['field_name'];
  }

}
