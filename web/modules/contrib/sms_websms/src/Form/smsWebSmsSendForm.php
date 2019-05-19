<?php

namespace Drupal\sms_websms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;

/**
 *
 */
class smsWebSmsSendForm extends FormBase {

  /**
   *
   */
  public function getFormId() {
    return 'sms_websms_Send_Form';
  }

  /**
   *
   */
  function buildForm(array $form, FormStateInterface $form_state) {
    $form['sms_websms_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Telephone number'),
      '#description'  =>$this->t('Telephone number'),
    ];

    $form['sms_websms_mes'] = [
      '#type' => 'textarea',
      '#title' =>$this-> t('Message'),
      '#description'  =>$this->t('Message text'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $valid = $form_state->getValue('sms_websms_phone');
    if (strlen($valid )> 11) {
      $form_state->setErrorByName('sms_websms_phone', $this->t('Value is too long'));
    }
    elseif (preg_match('/[^a-zA-Z0-9]/',$valid)) {
      $form_state->setErrorByName('sms_websms_phone', $this->t('Value contains wrong characters'));
    }
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('sms_websms.sms_websms_setting.settings');
    $login = $config->get('sms_websms_username');
    $password =$config->get('sms_websms_password');
    $message = $form_state->getValue('sms_websms_mes');
    $number = $form_state->getValue('sms_websms_phone');

    try {
      $client = \Drupal::httpClient();
      $url = 'http://cab.websms.ru/http_in6.asp';
      $url .= '?http_username=' . $login;
      $url .= '&http_password=' . $password;
      $url .= '&message=' . $message;
      $url .= '&phone_list=' . $number;
      $request = $client->get($url);
      $response = $request->getBody();

      if (!empty($response)) {
        if (stripos($response, 'error_num=OK')) {
          drupal_set_message($this-> t('Message sent'));
          $contents = (string)$response;
          \Drupal::logger('sms_websms')->notice($contents);
        }
        else {
          drupal_set_message($this-> t('Message not sent'),'error');
          $contents = (string)$response;
          $contents2 = explode("\n", $contents);
          drupal_set_message($contents2[1],'error');
          \Drupal::logger('sms_websms')->error($contents);
        }
      }
    }
    catch (RequestException $e) {
      return FALSE;
    }
  }
}
