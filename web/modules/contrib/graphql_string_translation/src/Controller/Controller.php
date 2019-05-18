<?php

namespace Drupal\graphql_string_translation\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Return response for manual check translations.
 */
class Controller extends ControllerBase {

  /**
   * Shows the string search screen.
   *
   * @return array
   *   The render array for the string search screen.
   */
  public function translatePage() {
    // Controller has been left here so that it's easy to re-add the filter form
    // once the context condition in core starts working correctly.

    $addStringForm = [
      '#type' => 'details',
      '#title' => $this->t('Add string'),
    ];

    $addStringForm['form'] = $this->formBuilder()->getForm('Drupal\graphql_string_translation\Form\AddStringForm');

    return [
      'add_string' => $addStringForm,
      'form' => $this->formBuilder()->getForm('Drupal\graphql_string_translation\Form\TranslateForm'),
    ];
  }

}
