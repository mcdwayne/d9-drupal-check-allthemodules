<?php

namespace Drupal\interface_string_stats\Controller;

use Drupal\locale\Controller\LocaleController;

/**
 * Extend the LocaleController so we can override the translate form.
 */
class StringStatsController extends LocaleController {

  /**
   * {@inheritdoc}
   */
  public function translatePage() {
    return [
      'filter' => $this->formBuilder()->getForm('Drupal\locale\Form\TranslateFilterForm'),
      'form' => $this->formBuilder()->getForm('Drupal\interface_string_stats\Form\StringStatsTranslateEditForm'),
    ];
  }

}
