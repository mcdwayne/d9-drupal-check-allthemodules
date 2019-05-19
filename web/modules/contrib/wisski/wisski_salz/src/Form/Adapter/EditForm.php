<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Form\Adapter\EditForm.
 */

namespace Drupal\wisski_salz\Form\Adapter;

use Drupal\Core\Form\FormStateInterface;

/**
 *  Provides an edit form for adapter.
 *
 * @see \Drupal\wisski_salz\Adapter\FormBase
 */
class EditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update adapter');
    $actions['delete']['#value'] = $this->t('Delete adapter');
    return $actions;
  }

}
