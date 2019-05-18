<?php

namespace Drupal\reltoabs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Middleware to alter REST responses.
 */
class ReltoabsSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'reltoabs_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'reltoabs.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = \Drupal::config('reltoabs.settings');
    $options = [
      "script" => "  JavaScripts SRC",
      "css" => "  CSS HREF",
      "images" => "  Images SRC",
      "links" => "  Hyperlinks HREF",
    ];

    $form['reltoabs_appid'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => '',
      '#default_value' => $config->get('reltoabs_appid'),
      '#prefix' => '<strong>Select checkbox where you want to convert relative paths to absolute URL.</strong> ',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('reltoabs.settings')->set('reltoabs_appid', $form_state->getValue('reltoabs_appid'))->save();

    parent::submitForm($form, $form_state);
  }

}
