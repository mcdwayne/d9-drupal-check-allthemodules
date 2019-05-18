<?php

namespace Drupal\module_builder\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Generic form for entering a section of data for a component.
 *
 * This determines which properties of the component to show from the values of
 * the entity type's code_builder annotation.
 *
 * @see \Drupal\module_builder\EntityHandler\ComponentSectionFormHandler
 */
class ComponentSectionForm extends ComponentFormBase {

   /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // TODO: remove this?

    return $form;
  }

}
