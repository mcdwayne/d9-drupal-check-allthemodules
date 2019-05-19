<?php

namespace Drupal\trance_example\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\trance\Form\TranceRevisionRevertForm;

/**
 * Provides a form for reverting a trance_example revision.
 */
class TranceExampleRevisionRevertForm extends TranceRevisionRevertForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('trance_example'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $trance_example_revision = NULL) {
    return parent::buildForm($form, $form_state, $trance_example_revision);
  }

}
