<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Form\Pipe\EditForm.
 */

namespace Drupal\wisski_pipe\Form\Pipe;

use Drupal\Core\Form\FormStateInterface;

/**
 *  Provides an edit form for pipe.
 *
 * @see \Drupal\wisski_pipe\Pipe\FormBase
 */
class EditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update pipe');
    $actions['delete']['#value'] = $this->t('Delete pipe');
    return $actions;
  }

}
