<?php

namespace Drupal\panels_extended\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\panels\Form\PanelsAddBlockForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * Improvements to the panels add block form.
 */
class ExtendedPanelsAddForm extends PanelsAddBlockForm {

  use FormValidationFixTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tempstore_id = NULL, $machine_name = NULL, $block_id = NULL, Request $request = NULL) {
    $form = parent::buildForm($form, $form_state, $tempstore_id, $machine_name, $block_id, $request);
    if (!empty($form['settings']['label'])) {
      // Disable the default label (which is the block name) and auto focus it.
      $form['settings']['label']['#default_value'] = NULL;
      $form['settings']['label']['#attributes']['autofocus'] = 'autofocus';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateFormWithErrorFix($form, $form_state);
  }

}
