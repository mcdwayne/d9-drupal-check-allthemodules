<?php
/**
 * @file
 * Contains \Drupal\dropkick\Controller\DropkickSettings.
 */

namespace Drupal\dropkick\Controller;

use Drupal\Core\Form\ConfigFormBase;

/**
 * DropkickSettings.
 */
class DropkickSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'demo_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $config = $this->config('dropkick.settings');

    $form['jquery_selector'] = array(
      '#type' => 'textarea',
      '#title' => t('Apply DropKick to the following elements'),
      '#description' => t('A jQuery selector to find elements to apply Dropkick
        to, such as <code>.dropkick-select</code>. Use <code>select</code> to
        apply Dropkick to all <code>&lt;select&gt;</code> elements. For multiple
        selector use comma separated selector.'),
      '#default_value' => $config->get('jquery_selector'),
    );

    $form['advance_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Advance Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['advance_settings']['mobile_device_support'] = array(
      '#type' => 'checkbox',
      '#title' => t('Mobile device support for DropKick'),
      '#description' => t('Render the Dropkick element for mobile devices.'),
      '#default_value' => $config->get('mobile_device_support'),
    );

    $form['advance_settings']['ie8_support'] = array(
      '#type' => 'checkbox',
      '#title' => t('IE 8 support for DropKick'),
      '#description' => t("Dropkick's own IE8 polyfill."),
      '#default_value' => $config->get('ie8_support'),
    );

    $form['advance_settings']['ui_theme'] = array(
      '#type' => 'select',
      '#title' => t('DropKick UI theme'),
      '#options' => array(
        'default' => t('Default'),
        'dropkick-classic' => t('DropKick Classic'),
      ),
      '#default_value' => $config->get('ui_theme'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $this->config('dropkick.settings')
      ->set('jquery_selector', $form_state->getValue('jquery_selector'))
      ->set('mobile_device_support', $form_state->getValue('mobile_device_support'))
      ->set('ie8_support', $form_state->getValue('ie8_support'))
      ->set('ui_theme', $form_state->getValue('ui_theme'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
