<?php

namespace Drupal\entity_backreference\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityBackReferenceConfigForm.
 *
 * @package Drupal\entity_backreference\Form
 */
class EntityBackReferenceConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}EntityBackReferenceConfigForm
   */
  protected function getEditableConfigNames() {
    return [
      'entity_backreference.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_back_reference_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_backreference.settings');
    $form['reindex'] = array(
      '#type' => 'fieldset',
      '#title' => t('Entity BackReference Reindexing'),
    );

    $form['reindex']['entity_updates_reindex'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reindex Items'),
      '#description' => $this->t('Reindex Items On Entity Changes'),
      '#default_value' => $config->get('entity_updates_reindex'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('entity_backreference.settings')
      ->set('entity_updates_reindex', $form_state->getValue('entity_updates_reindex'))
      ->save();
  }

}
