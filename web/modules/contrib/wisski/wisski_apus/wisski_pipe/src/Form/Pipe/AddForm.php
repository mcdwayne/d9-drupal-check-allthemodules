<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Form\Pipe\AddForm.
 */

namespace Drupal\wisski_pipe\Form\Pipe;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for profile addition forms.
 *
 * @see \Drupal\wisski_pipe\Pipe\FormBase
 */
class AddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save and manage processors');
    return $actions;
  }

}
