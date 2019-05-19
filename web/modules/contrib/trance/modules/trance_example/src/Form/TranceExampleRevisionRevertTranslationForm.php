<?php

namespace Drupal\trance_example\Form;

use Drupal\Core\Form\FormStateInterface;

use Drupal\trance\Form\TranceRevisionRevertTranslationForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form for reverting a TranceExample revision for a single translation.
 */
class TranceExampleRevisionRevertTranslationForm extends TranceRevisionRevertTranslationForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('trance_example'),
      $container->get('date.formatter'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $trance_example_revision = NULL, $langcode = NULL) {
    return parent::buildForm($form, $form_state, $trance_example_revision, $langcode);
  }

}
