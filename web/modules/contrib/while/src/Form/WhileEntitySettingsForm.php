<?php

namespace Drupal\white_label_entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * While entity settings page.
 */
class WhileEntitySettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'while_entity_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('white_label_entity.settings');
    $entity_type_name_plural = $config->get('entity_type_name_plural');

    $form['WhileEntity_settings']['#markup'] = $this->t('Settings form for @entity_type_name_plural. Manage field settings here.', ['@entity_type_name_plural' => $entity_type_name_plural]);
    return $form;
  }

}
