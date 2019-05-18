<?php

namespace Drupal\ext_js\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ExtJsSettingsForm.
 */
class ExtJsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
        'ext_js.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ext_js_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['ext_js_files'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Ext JS files'),
      '#default_value' => $this->config('ext_js.settings')->get('files'),
      '#description' => $this->t('The character "|" can be used to separate Ext JS files,for example: aa|bb|cc.'),
    );

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

    $this->config('ext_js.settings')
    ->set('files', $form_state->getValue('ext_js_files'))
    ->save();
    parent::submitForm($form, $form_state);

  }

}
