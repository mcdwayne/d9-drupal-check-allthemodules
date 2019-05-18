<?php

namespace Drupal\box_clone\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class BoxCloneSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['box_clone.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'box_clone_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('box_clone.settings');
    $form['cloned_box_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default title'),
      '#default_value' => $config->get('cloned_box_title'),
      '#description' => $this->t('Enter the default title for cloned box.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cloned_box_title = $form_state->getValue('cloned_box_title');
    $this->config('box_clone.settings')->set('cloned_box_title', $cloned_box_title)->save();
  }

}
