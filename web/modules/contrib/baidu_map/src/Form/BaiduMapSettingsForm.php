<?php

namespace Drupal\baidu_map\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a nice menus settings form.
 */
class BaiduMapSettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'baidu_map_settings';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'baidu_map.settings',
    ];
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('baidu_map.settings');

    // A Baidu Map API Key has exactly 24 or 32 alphanumeric characters.
    $form['baidu_map_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Baidu Map API Key'),
      '#required' => TRUE,
      '#description' => $this->t('Configure the <em>Baidu Map API Key</em> <br/>A Key could be obtained by applying for an account on the <a href="@link_baidu_api" target="_blank">Baidu API</a> website.', array('@link_baidu_api' => 'http://lbsyun.baidu.com/apiconsole/key')),
      '#default_value' => $config->get('baidu_map_api_key'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('baidu_map.settings')
      ->set('baidu_map_api_key', $form_state->getValue('baidu_map_api_key'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
