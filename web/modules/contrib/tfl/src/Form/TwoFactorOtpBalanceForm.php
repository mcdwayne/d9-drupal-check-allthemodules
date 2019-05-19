<?php

namespace Drupal\tfl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tfl\Controller\Api2Factor;

/**
 * {@inheritdoc}
 */
class TwoFactorOtpBalanceForm extends FormBase {


  /**
   * Api2Factor.
   *
   * @var \Drupal\tfl\Controller\Api2Factor
   */
  protected $api2Factor;

  /**
   * Constructor method.
   *
   *
   */
  public function __construct() {
    $this->api2Factor = new Api2Factor();
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tfl_otp_balance_form';
  }
  

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $sms_api_data = $this->api2Factor->getBalance('SMS');
    $voice_api_data = $this->api2Factor->getBalance('VOICE');
    $promotional_api_data = $this->api2Factor->getBalance('PROMOTIONAL_SMS', TRUE);
    $transactional_api_data = $this->api2Factor->getBalance('TRANSACTIONAL_SMS', TRUE);
    
    if (isset($sms_api_data) && $sms_api_data->Status == 'Error') {
      drupal_set_message($sms_api_data->Details, 'error', TRUE);
    }
    else if (isset($voice_api_data) && $voice_api_data->Status == 'Error') {
      drupal_set_message($voice_api_data->Details, 'error', TRUE);
    }
    else if (isset($promotional_api_data) && $promotional_api_data->Status == 'Error') {
      drupal_set_message($promotional_api_data->Details, 'error', TRUE);
    }
    else if (isset($transactional_api_data) && $transactional_api_data->Status == 'Error') {
      drupal_set_message($transactional_api_data->Details, 'error', TRUE);
    }
    else if (is_null($sms_api_data) && is_null($sms_api_data) && is_null($promotional_api_data) && is_null($transactional_api_data)) {
      drupal_set_message('API connection error found due to unreachable internet.', 'error', TRUE);
    }
   
  if (isset($sms_api_data) && $sms_api_data->Status == 'Success') {
    $form['otp_balance'] = [
      '#type' => 'details',
      '#title' => $this->t( 'OTP Balance' ),
      '#open' => TRUE,
    ];
    $header1 = [
      'sms' => 'For SMS',
      'voice' => 'For VOICE',
    ];
    $output1 = [];
    $output1[] = [
      'sms' => isset($sms_api_data->Details) ? $sms_api_data->Details : '',
      'voice' => isset($voice_api_data->Details) ? $voice_api_data->Details : '',
    ];
    
    $form['otp_balance']['table'] = [
        '#type' => 'table',
        '#header' => $header1,
        '#rows' => $output1,
        '#empty' => t('API is not working.'),
    ];    
  }  
    // For additional services     
    
    if (isset($promotional_api_data) && $promotional_api_data->Status == 'Success') {
        $form['additional_balance'] = [
          '#type' => 'details',
          '#title' => $this->t( 'Additional Services' ),
          '#open' => TRUE,
        ];
        $header2 = [
          'promotional' => 'For Promotional SMS',
          'transactional' => 'For Transactional SMS',
        ];
        $output2 = [];
        $output2[] = [
          'promotional' => isset($promotional_api_data->Details) ? $promotional_api_data->Details : '',
          'transactional' => isset($transactional_api_data->Details) ? $transactional_api_data->Details : '',
        ];    

        $form['additional_balance']['table'] = [
            '#type' => 'table',
            '#header' => $header2,
            '#rows' => $output2,
            '#empty' => t('API is not working.'),
        ];    
    }

    return $form;
  }

 /**
   * {@inheritdoc} 
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
   
  }


}
