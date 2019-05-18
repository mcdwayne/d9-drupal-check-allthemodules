<?php

/**
 * @file
 * Contains \Drupal\drowl_trademark\Form\DrowlTrademarkSettingsForm.
 */

namespace Drupal\drowl_trademark\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class DrowlTrademarkSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drowl_trademark_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('drowl_trademark.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['drowl_trademark.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $module_path = drupal_get_path('module', 'drowl_trademark');

    $form['drowl_trademark_replacements'] = [
      '#type' => 'textfield',
      '#title' => 'Append ® to these words',
      '#description' => t("Enter words comma separated to append a <sup>®</sup> to dynamically at runtime."),
      '#required' => true,
      '#default_value' => $site_name = \Drupal::config('drowl_trademark.settings')->get('drowl_trademark_replacements'),
    ];

    $form['drowl_trademark_filter'] = [
      '#type' => 'textfield',
      '#title' => 'Filter by jQuery filters (exclusion)',
      '#description' => t("Use jQuery filters to skip matching elements, separated by comma. Regular jQuery notation. Default: \".no-drowl-trademark,a[itemprop=email]\". To include parents, for example required for the spamspan module, you may use the inperformant \".spamspan > *\" filter."),
      '#required' => true,
      '#default_value' => $site_name = \Drupal::config('drowl_trademark.settings')->get('drowl_trademark_filter'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
?>
