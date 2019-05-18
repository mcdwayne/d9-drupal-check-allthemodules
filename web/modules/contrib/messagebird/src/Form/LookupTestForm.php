<?php

namespace Drupal\messagebird\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;

/**
 * Class MessagebirdTestForm.
 *
 * @package Drupal\messagebird
 */
class LookupTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'messagebird_test_lookup';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['number'] = array(
      '#type' => 'tel',
      '#title' => t('Telephone number'),
      '#required' => TRUE,
      '#description' => t('Multiple formats are supported based on the optional country code.'),
    );

    $form['country'] = array(
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#description' => $this->t('Choose the country of the %title origin.', array('%title' => $form['number']['#title'])),
      '#title_display' => 'before',
      '#empty_option' => $this->t('- Choose  -'),
      '#options' => CountryManager::getStandardList(),
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Lookup number'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $country = $form_state->isValueEmpty('country') ? NULL : $form_state->getValue('country');

    /** @var \Drupal\messagebird\MessageBirdLookupInterface $lookup_service */
    $lookup_service = \Drupal::service('messagebird.lookup');
    $lookup_service->lookupNumber($form_state->getValue('number'), $country);

    if ($lookup_service->hasValidLookup()) {
      if ($this->config('messagebird.settings')->get('debug.mode')) {
        return;
      }

      $number = $lookup_service->getFormatInternational();
      drupal_set_message($this->t('Valid phone number: %number', array('%number' => $number)));
    }
    else {
      drupal_set_message($this->t('Invalid phone number.'), 'error');
    }
  }

}
