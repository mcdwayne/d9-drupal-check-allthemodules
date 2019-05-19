<?php

namespace Drupal\silktide\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SilktideForm.
 *
 * @package Drupal\silktide\Form
 */
class SilktideForm extends ConfigFormBase {

  /**
   * Getter method for Form ID.
   *
   * The form ID is used in implementations of hook_form_alter() to allow other
   * modules to alter the render array built by this form controller. It must be
   * unique site wide. It normally starts with the providing module's name.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'silktide_form';
  }

  /**
   * Build the simple form.
   *
   * A build form method constructs an array that defines how markup and
   * other form elements are included in an HTML form.
   *
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('silktide.settings');

    $form['description'] = [
      '#type' => 'item',
      '#markup' => sprintf($this->t('Please see the <a href="%s">configuration guide</a> or contact <a href="%s">Silktide</a> for assistance.'),
          'https://support.silktide.com/guides/cms-install-drupal-guide/',
          'https://www.silktide.com'
      ),
    ];

    $form['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key:'),
      '#default_value' => $config->get('apikey'),
      '#maxlength' => 32,
      '#length' => 32,
      '#required' => TRUE,
      '#description' => $this->t('The API key as shown on your Silktide account CMS page'),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this
        ->t('Submit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Indicate which fields are configurable.
   *
   * @return array
   *   The settings fields.
   */
  public function getEditableConfigNames() {
    return ['silktide.settings'];
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $apiKey = trim($form_state->getValue('apikey'));

    if (strlen($apiKey) !== 32) {
      $form_state
        ->setErrorByName(
          'apikey',
          $this->t('The API Key must be 32 characters long.'));
    }

    if (!preg_match('/^[a-z0-9]{32}$/i', $apiKey)) {
      $form_state
        ->setErrorByName(
          'apikey',
          $this->t(
            'Sorry, that API key was invalid. It must be a 32 character long code from Silktide.com'
          )
        );
    }

  }

  /**
   * Implements a form submit handler.
   *
   * The submitForm method is the default method called for any submit elements.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('silktide.settings');
    $config->set('apikey', trim($form_state->getValue('apikey')));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
