<?php

namespace Drupal\path_theme\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'path_theme_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('path_theme.settings')
      ->set('paths', $this->explodeOptions($form_state->getValue('paths')))
      ->save();

    parent::submitForm($form, $form_state);
  }

  protected function explodeOptions($value) {
    $options = [];

    $newLines = '/(\r\n|\r|\n)/';

    $values = preg_split($newLines, $value);

    foreach ($values as $value) {
      $valueArray = explode('|', $value);
      $key = array_shift($valueArray);
      $val = !empty($valueArray) ? array_shift($valueArray) : '';

      $options[$key] = $val;
    }

    return $options;
  }

  protected function implodeOptions($options) {
    $value = '';

    if (!empty($options)) {
      foreach ($options as $key => $val) {
        if (empty($key)) {
          continue;
        }

        if (!empty($value)) {
          $value .= "\r\n";
        }

        $value .= $key;

        if (!empty($val)) {
          $value .= '|' . $val;
        }
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['path_theme.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('path_theme.settings');

    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#description' => $this->t('Enter a list of paths, with wildcards, followed by a | and the name of the theme. Leave the second part off to use the default front-end theme.'),
      '#default_value' => $this->implodeOptions($config->get('paths')),
    ];

    return parent::buildForm($form, $form_state);
  }
}
