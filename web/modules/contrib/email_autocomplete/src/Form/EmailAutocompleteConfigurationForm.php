<?php

namespace Drupal\email_autocomplete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class EmailAutocompleteConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_autocomplete_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'email_autocomplete.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('email_autocomplete.settings');

    $form['#tree'] = TRUE;

    // Initial number of names.
    if (!$form_state->get('num_domains')) {
      $form_state->set('num_domains', 1);
    }

    // Default autocomplete extentions.
    $default_domains = 'yahoo.com, hotmail.com, gmail.com, me.com, aol.com, mac.com, live.com, comcast.net, googlemail.com, msn.com, hotmail.co.uk, yahoo.co.uk, facebook.com, verizon.net, sbcglobal.net, att.net, gmx.com, outlook.com, icloud.com';

    // Container for our repeating fields.
    $form['domains'] = [
      '#type' => 'container',
      '#markup' => $this->t('<p><strong>Email autocomplete</strong> module provides you with the following popular email extensions: <strong><em>@default_domains</em>.</p><p><em>Note:</em></strong> You can add any number of additional autocomplete domains using in the same format of <strong><em>example.com</em></strong>.</p>', ['@default_domains' => $default_domains]),
    ];

    // Domains field.
    $form['domains'][0] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domains'),
      '#default_value' => $config->get('domains.0'),
    ];

    // Number of allowed values.
    $num_domains = $form_state->get('num_domains') + count($config->get('domains'));

    // Loop through number of domains.
    for ($x = 1; $x < $num_domains; $x++) {
      $form['domains'][$x] = [
        '#type' => 'textfield',
        '#default_value' => $config->get('domains.' . $x),
      ];
    }

    // Button to add more domains.
    $form['addname'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another domain'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Handle adding new.
   */
  private function addNewFields(array &$form, FormStateInterface $form_state) {
    // Add 1 to the number of domains.
    $num_domains = $form_state->get('num_domains');
    $form_state->set('num_domains', ($num_domains + 1));
    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Decide what action to take based on which button the user clicked.
    switch ($values['op']) {
      case 'Add another domain':
        $this->addNewFields($form, $form_state);
        break;

      default:
        $this->finalSubmit($form, $form_state);
    }
  }

  /**
   * Handle submit.
   */
  private function finalSubmit(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('email_autocomplete.settings')
      ->set('domains', array_filter($values['domains']))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
