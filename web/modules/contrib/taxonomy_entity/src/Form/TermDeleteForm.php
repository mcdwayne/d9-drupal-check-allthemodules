<?php

namespace Drupal\taxonomy_entity\Form;

use Drupal\taxonomy\Form\TermDeleteForm as CoreTermDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a deletion confirmation form for taxonomy term.
 */
class TermDeleteForm extends CoreTermDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vocabulary = $form_state->get(['taxonomy', 'vocabulary']);
    $hierarchy = $vocabulary->getHierarchy();

    parent::submitForm($form, $form_state);

    if ($vocabulary->getHierarchy !== $hierarchy) {
      $vocabulary->setHierarchy($hierarchy);
      $vocabulary->save();
    }
  }

}
