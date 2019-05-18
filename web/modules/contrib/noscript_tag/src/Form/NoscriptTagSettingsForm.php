<?php

namespace Drupal\noscript_tag\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Class NoscriptTagSettingsForm.
 *
 * Implements the Configuration form builder.
 *
 * @package Drupal\noscript_tag\Form
 */
class NoscriptTagSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['noscript_tag.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'noscript_tag_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the config for module.
    $config = $this->config('noscript_tag.settings');
    // Form builder.
    $form = [];
    $form = [
      'noscript_tag_value' => [
        '#type' => 'text_format',
        '#title' => $this->t('Enter message to display in noscript tag:'),
        '#description' => $this->t('Please enter your message which you want to display users that have disabled scripts in their browser or have a browser that does not support script tag.'),
        '#default_value' => $config->get('noscript_tag_value') ? $config->get('noscript_tag_value') : $this->t('Your browser does not support javascript!'),
        '#format' => $config->get('noscript_tag_format') ? $config->get('noscript_tag_format') : 'basic_html',
        '#rows' => 5,
        '#cols' => 10,
        '#resizable' => TRUE,
      ],
    ];
    // Return the form.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submitted Message.
    $submitted_message = $form_state->getValue('noscript_tag_value');
    // Extract the value.
    $noscript_tag_value = $submitted_message['value'];
    $noscript_tag_format = $submitted_message['format'];
    // Save to config.
    $this->config('noscript_tag.settings')
      ->set('noscript_tag_value', $noscript_tag_value)
      ->set('noscript_tag_format', $noscript_tag_format)
      ->save();
    // Call the parent Submit function.
    parent::submitForm($form, $form_state);
  }

}
