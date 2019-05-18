<?php

namespace Drupal\form_alter_service_test;

use Drupal\Core\Form\FormStateInterface;
use Drupal\form_alter_service\FormAlterBase;
use Drupal\views\ViewExecutable;

/**
 * {@inheritdoc}
 */
class UsersListFormAlterTest extends FormAlterBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, ViewExecutable $view = NULL, array &$output = NULL) {
    $view->setTitle($this->t('Test title'));
    $output[0]['#view']->setTitle($this->t('Test title 2'));

    $form['test_view_argument'] = [
      '#markup' => $view->getTitle(),
    ];
  }

}
