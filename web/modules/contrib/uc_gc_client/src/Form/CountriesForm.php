<?php

namespace Drupal\uc_gc_client\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\uc_country\Controller\CountryController;

/**
 *
 */
class CountriesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_gc_client_countries_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $countryController = new CountryController();
    $uc_countries = $countryController->countryOptionsCallback();
    $gc_countries = \Drupal::config('uc_gc_client.settings')->get('countries');

    $countries = [];
    $default_value = [];

    foreach($gc_countries as $gc_code => $gc_country) {
      if (isset($uc_countries[$gc_code])) {
        $country_name = $uc_countries[$gc_code]->render();
        $countries_[$country_name] = $gc_code;
      }
    }
    ksort($countries_);

    foreach($countries_ as $name => $gc_code) {

      $region = $gc_countries[$gc_code]['region'];
      switch ($region) {
        case 'bacs':
          $scheme = 'Bacs';
          break;
        case 'sepa_core':
          $scheme = 'SEPA';
          break;
        case 'becs':
          $scheme = 'BECS';
          break;
        case 'betalingsservice':
          $scheme = 'Betalingsservice';
          break;
        case 'autogiro':
          $scheme = 'Autogiro';
          break;
        case 'pnz':
          $scheme = 'PaymentsNZ';
          break;
        default:
          $scheme = $region;
      }

      $countries[$gc_code] = array(
        $name,
        $scheme,
        $gc_countries[$gc_code]['sign'],
      );
      if ($gc_countries[$gc_code]['enabled']) $default_value[$gc_code] = TRUE;
    }

    $form = [];
    $form['markup'] = [
      '#markup' => t('<p>Countries should first be enabled in Ubercart before they can be enabled / disabled here.</p>'),
    ];
    $header = ['Country', 'Direct debit scheme', 'Currency'];
    $form['countries'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $countries,
      '#empty' => t('No countries available.'),
      '#default_value' => $default_value,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Enable / disable countries'),
    ];
    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $countries = $form_state->getValue(['countries']);
    $config = \Drupal::service('config.factory')->getEditable('uc_gc_client.settings');
    $gc_countries = $config->get('countries');

    foreach ($countries as $code => $country) {
      if ($country) {
        $gc_countries[$code]['enabled'] = 1;
      }
      else {
        $gc_countries[$code]['enabled'] = 0;
      }
    }
    $config->set('countries', $gc_countries)->save();
  }
}
