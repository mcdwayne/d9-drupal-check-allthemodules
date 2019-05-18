<?php

namespace Drupal\simplefader\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simplefader\SimpleFaderCommonFunctions;
/**
 * Configure simplefader settings for this site.
 */
class SimpleFaderAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simplefader_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simplefader.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $selected_element = \Drupal::config('simplefader.settings')->get('simplefader_selected_element');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['simplefader_selected_element'] = [
      '#default_value' => \Drupal::config('simplefader.settings')->get('simplefader_selected_element'),
      '#description' => $this->t("One per line, please enter the classes or ID's along with the leading class (.) or ID (#) selector."),
      '#required' => TRUE,
      '#title' => $this->t('CSS classes or ID\'s to close'),
      '#type' => 'textarea',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('simplefader.settings');
    $config
      ->set('simplefader_selected_element', $form_state->getValue('simplefader_selected_element'))

      ->save();

    parent::submitForm($form, $form_state);
    \Drupal::service("router.builder")->rebuild();
  }
}
