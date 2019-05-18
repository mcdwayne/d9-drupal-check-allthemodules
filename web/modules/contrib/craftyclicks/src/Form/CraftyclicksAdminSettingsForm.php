<?php

namespace Drupal\craftyclicks\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CraftyclicksAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'craftyclick_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['craftyclicks.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('craftyclicks.settings');

    $key = $config->get('craftyclicks.key');
    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#required' => TRUE,
      '#default_value' => isset($key) ? $key : 'xxxxx-xxxxx-xxxxx-xxxxx',
    ];
    $url = $config->get('craftyclicks.url');
    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#required' => TRUE,
      '#default_value' => isset($url) ? $url : 'http://pcls1.craftyclicks.co.uk/xml/rapidaddress',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('craftyclicks.settings');
    $config
      ->set('key', $form_state->getValue('key'))
      ->set('url', $form_state->getValue('url'))
      ->save();

    parent::submitForm($form, $form_state);
  }


}