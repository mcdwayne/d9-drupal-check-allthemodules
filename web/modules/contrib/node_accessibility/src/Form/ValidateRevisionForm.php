<?php

namespace Drupal\node_accessibility\Form;

use Drupal\node_accessibility\Form\ValidateFormBase;

/**
 * Form controller for the node_accessibility entity validate form.
 *
 * @ingroup node_accessibility
 */
class ValidateRevisionForm extends ValidateFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_accessibility_validate_revision';
  }
}
