<?php

namespace Drupal\unveil\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class UnveilSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unveil_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('unveil.settings');

    $form['unveil_preprocess_image'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Unveil.js lazyloading'),
        '#default_value' => $config->get('unveil_preprocess_image'),
    );

    $form['unveil_preprocess_image_unveiled'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Unveiled distance'),
        '#description' => 'The distance from viewport when images are unveiled.',
        '#default_value' => $config->get('unveil_preprocess_image_unveiled') ,
        '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^([0-9]+)$/', $form_state->getValue('unveil_preprocess_image_unveiled'))) {
      $form_state->setErrorByName('unveil_preprocess_image_unveiled', t('Unveiled distance must be numeric'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('unveil.settings')
      ->set('unveil_preprocess_image', $form_state->getValue('unveil_preprocess_image'))
      ->set('unveil_preprocess_image_unveiled', $form_state->getValue('unveil_preprocess_image_unveiled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['unveil.settings'];
  }
}


