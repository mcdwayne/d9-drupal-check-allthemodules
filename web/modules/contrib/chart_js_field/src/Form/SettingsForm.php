<?php
/**
 * @file
 * Contains Drupal\chart_js_field\Form\MessagesForm.
 */
namespace Drupal\chart_js_field\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'chart_js_field.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chart_js_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('chart_js_field.settings');

    $form['external'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use CDN'),
      '#default_value' => $config->get('external'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('chart_js_field.settings')
      ->set('external', $form_state->getValue('external'))
      ->save();
  }

}
