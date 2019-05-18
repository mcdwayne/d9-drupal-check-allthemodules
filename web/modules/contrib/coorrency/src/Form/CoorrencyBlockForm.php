<?php

namespace Drupal\coorrency\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Coorrency block form.
 */
class CoorrencyBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coorrency_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['from'] = [
      '#type' => 'select',
      '#title' => $this->t('From'),
      '#options' => $this->getCurrencies(),
      '#required' => TRUE,
      '#attached' => [
        'library' => [
          'coorrency/coorrency',
        ],
      ],
    ];

    $config = \Drupal::config('coorrency.settings');
    $use_swap = $config->get('coorrency.swap');

    if ($use_swap) {
      $form['swap'] = [
        '#markup' => '<span class="coorrency-swap">⇅</span>',
      ];
    }

    $form['to'] = [
      '#type' => 'select',
      '#title' => $this->t('To'),
      '#options' => $this->getCurrencies(),
      '#required' => TRUE,
    ];

    $form['amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
    ];

    // Submit.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert'),
    ];

    $form['coorrency'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => ['coorrency-rate'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $from = $form_state->getValue('from');
    $to = $form_state->getValue('to');

    $form_state->setRedirect(
      'coorrency.convert',
      [
        'from' => $from,
        'to' => $to,
      ]
    );
  }

  /**
   * Function to gather all the available currencies.
   */
  protected function getCurrencies() {
    return [
      'CAD' => 'CAD',
      'USD' => 'USD',
      'AED' => 'AED',
      'AFN' => 'AFN',
      'ALL' => 'ALL',
      'AMD' => 'AMD',
      'ANG' => 'ANG',
      'AOA' => 'AOA',
      'ARS' => 'ARS',
      'AUD' => 'AUD',
      'AWG' => 'AWG',
      'AZN' => 'AZN',
      'BAM' => 'BAM',
      'BBD' => 'BBD',
      'BDT' => 'BDT',
      'BGN' => 'BGN',
      'BHD' => 'BHD',
      'BIF' => 'BIF',
      'BMD' => 'BMD',
      'BND' => 'BND',
      'BOB' => 'BOB',
      'BRL' => 'BRL',
      'BSD' => 'BSD',
      'BTC' => 'BTC',
      'BTN' => 'BTN',
      'BWP' => 'BWP',
      'BYN' => 'BYN',
      'BZD' => 'BZD',
      'CDF' => 'CDF',
      'CHF' => 'CHF',
      'CLP' => 'CLP',
      'CNY' => 'CNY',
      'COP' => 'COP',
      'CRC' => 'CRC',
      'CUC' => 'CUC',
      'CUP' => 'CUP',
      'CVE' => 'CVE',
      'CZK' => 'CZK',
      'DJF' => 'DJF',
      'DKK' => 'DKK',
      'DOP' => 'DOP',
      'DZD' => 'DZD',
      'EGP' => 'EGP',
      'ERN' => 'ERN',
      'ETB' => 'ETB',
      'EUR' => 'EUR',
      'FJD' => 'FJD',
      'FKP' => 'FKP',
      'GBP' => 'GBP',
      'GEL' => 'GEL',
      'GGP' => 'GGP',
      'GHS' => 'GHS',
      'GIP' => 'GIP',
      'GMD' => 'GMD',
      'GNF' => 'GNF',
      'GTQ' => 'GTQ',
      'GYD' => 'GYD',
      'HKD' => 'HKD',
      'HNL' => 'HNL',
      'HRK' => 'HRK',
      'HTG' => 'HTG',
      'HUF' => 'HUF',
      'IDR' => 'IDR',
      'ILS' => 'ILS',
      'IMP' => 'IMP',
      'INR' => 'INR',
      'IQD' => 'IQD',
      'IRR' => 'IRR',
      'ISK' => 'ISK',
      'JEP' => 'JEP',
      'JMD' => 'JMD',
      'JOD' => 'JOD',
      'JPY' => 'JPY',
      'KES' => 'KES',
      'KGS' => 'KGS',
      'KHR' => 'KHR',
      'KMF' => 'KMF',
      'KPW' => 'KPW',
      'KRW' => 'KRW',
      'KWD' => 'KWD',
      'KYD' => 'KYD',
      'KZT' => 'KZT',
      'LAK' => 'LAK',
      'LBP' => 'LBP',
      'LKR' => 'LKR',
      'LRD' => 'LRD',
      'LSL' => 'LSL',
      'LYD' => 'LYD',
      'MAD' => 'MAD',
      'MDL' => 'MDL',
      'MGA' => 'MGA',
      'MKD' => 'MKD',
      'MMK' => 'MMK',
      'MNT' => 'MNT',
      'MOP' => 'MOP',
      'MRO' => 'MRO',
      'MUR' => 'MUR',
      'MVR' => 'MVR',
      'MWK' => 'MWK',
      'MXN' => 'MXN',
      'MYR' => 'MYR',
      'MZN' => 'MZN',
      'NAD' => 'NAD',
      'NGN' => 'NGN',
      'NIO' => 'NIO',
      'NOK' => 'NOK',
      'NPR' => 'NPR',
      'NZD' => 'NZD',
      'OMR' => 'OMR',
      'PAB' => 'PAB',
      'PEN' => 'PEN',
      'PGK' => 'PGK',
      'PHP' => 'PHP',
      'PKR' => 'PKR',
      'PLN' => 'PLN',
      'PYG' => 'PYG',
      'QAR' => 'QAR',
      'RON' => 'RON',
      'RSD' => 'RSD',
      'RUB' => 'RUB',
      'RWF' => 'RWF',
      'SAR' => 'SAR',
      'SBD' => 'SBD',
      'SCR' => 'SCR',
      'SDG' => 'SDG',
      'SEK' => 'SEK',
      'SGD' => 'SGD',
      'SHP' => 'SHP',
      'SLL' => 'SLL',
      'SOS' => 'SOS',
      'SPL' => 'SPL',
      'SRD' => 'SRD',
      'STD' => 'STD',
      'SVC' => 'SVC',
      'SYP' => 'SYP',
      'SZL' => 'SZL',
      'THB' => 'THB',
      'TJS' => 'TJS',
      'TMT' => 'TMT',
      'TND' => 'TND',
      'TOP' => 'TOP',
      'TRY' => 'TRY',
      'TTD' => 'TTD',
      'TVD' => 'TVD',
      'TWD' => 'TWD',
      'TZS' => 'TZS',
      'UAH' => 'UAH',
      'UGX' => 'UGX',
      'UYU' => 'UYU',
      'UZS' => 'UZS',
      'VEF' => 'VEF',
      'VND' => 'VND',
      'VUV' => 'VUV',
      'WST' => 'WST',
      'XAF' => 'XAF',
      'XCD' => 'XCD',
      'XDR' => 'XDR',
      'XOF' => 'XOF',
      'XPF' => 'XPF',
      'YER' => 'YER',
      'ZAR' => 'ZAR',
      'ZMW' => 'ZMW',
      'ZWD' => 'ZWD',
    ];
  }

}
