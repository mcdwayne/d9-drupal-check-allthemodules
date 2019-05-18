<?php

namespace Drupal\baidumap_fieldtype\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure book settings for this site.
 */
class BaidumapFieldtypeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'baidumap_fieldtype_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['baidumap_fieldtype.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $types = node_type_get_names();
    $config = $this->config('baidumap_fieldtype.settings');
    $form['baidumap_ak'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set the ak of baidu map'),
      '#default_value' => $config->get('baidumap_ak'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('baidumap_fieldtype.settings')
      ->set('baidumap_ak', $form_state->getValue('baidumap_ak'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
