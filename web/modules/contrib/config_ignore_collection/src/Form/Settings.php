<?php

namespace Drupal\config_ignore_collection\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a setting UI for Config Collection Ignore.
 *
 * @package Drupal\config_ignore_collection\Form
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'config_ignore_collection.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_ignore_collection_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $description = $this->t('One collection name per line.<br />
Examples: <ul>
<li>language</li>
<li>collection1</li>
<li>collection2</li>
</ul>');

    $config_ignore_settings = $this->config('config_ignore_collection.settings');
    $form['ignored_config_collections'] = [
      '#type' => 'textarea',
      '#rows' => 25,
      '#title' => $this->t('Configuration collection names to ignore'),
      '#description' => $description,
      '#default_value' => implode(PHP_EOL, $config_ignore_settings->get('ignored_config_collections')),
      '#size' => 60,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config_ignore_settings = $this->config('config_ignore_collection.settings');
    $config_ignore_settings_array = preg_split("[\n|\r]", $values['ignored_config_collections']);
    $config_ignore_settings_array = array_filter($config_ignore_settings_array);
    $config_ignore_settings->set('ignored_config_collections', $config_ignore_settings_array);
    $config_ignore_settings->save();
    parent::submitForm($form, $form_state);
  }

}
