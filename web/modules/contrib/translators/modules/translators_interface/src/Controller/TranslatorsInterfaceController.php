<?php

namespace Drupal\translators_interface\Controller;

use Drupal\translators_interface\Form\TranslateEditForm;
use Drupal\translators_interface\Form\TranslateFilterForm;
use Drupal\locale\Controller\LocaleController;

/**
 * Class TranslatorsInterfaceController.
 *
 * @package Drupal\translators_interface\Controller
 */
class TranslatorsInterfaceController extends LocaleController {

  /**
   * {@inheritdoc}
   */
  public function translatePage() {
    $builder = $this->formBuilder();
    return [
      'filter' => $builder->getForm(TranslateFilterForm::class),
      'form'   => $builder->getForm(TranslateEditForm::class),
    ];
  }

}
