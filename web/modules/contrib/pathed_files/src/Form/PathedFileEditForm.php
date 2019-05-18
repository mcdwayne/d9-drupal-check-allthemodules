<?php

namespace Drupal\pathed_file\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class PathedFileEditForm
 *
 * Provides the edit form for our pathed file entity.
 *
 * @package Drupal\pathed_file\Form
 *
 * @ingroup pathed_file
 */
class PathedFileEditForm extends PathedFileFormBase {

  /**
   * {inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Update pathed file');
    return $actions;
  }

  /**
   * {inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
  }
}
