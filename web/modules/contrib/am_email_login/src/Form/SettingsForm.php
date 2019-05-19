<?php
/**
 * @file
 * Contains Drupal\am_registration\Form\SettingsForm.
 */
namespace Drupal\am_registration\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
/**
 * Class SettingsForm.
 *
 * @package Drupal\xai\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_config = \Drupal::config('am_registration.settings');
    return array(
      'subject' => $default_config->get('subject'),
      'body' => $default_config->get('body'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'am_registration.settings',
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('am_registration.settings');
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('subject'),
      '#rows' => 15,
    );
    $form['body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('body'),
      '#rows' => 15,
    );
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
    parent::submitForm($form, $form_state);
    $this->config('am_registration.settings')
      ->set('body', $form_state->getValue('body'))
      ->set('subject', $form_state->getValue('subject'))
      ->save();
  }
}