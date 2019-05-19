<?php

namespace Drupal\trance_example\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\trance\Form\TranceRevisionDeleteForm;

/**
 * Provides a form for reverting a trance_example revision.
 */
class TranceExampleRevisionDeleteForm extends TranceRevisionDeleteForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('trance_example'),
      $entity_manager->getStorage('trance_example_type'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $trance_example_revision = NULL) {
    return parent::buildForm($form, $form_state, $trance_example_revision);
  }

}
