<?php

namespace Drupal\local_translation_interface\Controller;

use Drupal\local_translation_interface\Form\TranslateEditForm;
use Drupal\local_translation_interface\Form\TranslateFilterForm;
use Drupal\locale\Controller\LocaleController;

/**
 * Class LocalTranslationInterfaceController.
 *
 * @package Drupal\local_translation_interface\Controller
 */
class LocalTranslationInterfaceController extends LocaleController {

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
