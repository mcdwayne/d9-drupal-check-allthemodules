<?php
/**
 * @file
 */
namespace Drupal\sms_mobio\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
/**
 * Implements an example form.
 */
class SmsMobioCheckCode extends FormBase {
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'sms_mobio_check_code';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config = NULL) {

    $form['sms_mobio_service1_servid'] = array(
      '#type' => 'hidden',
      '#value' => $config['sms_mobio_service1_servid'],
    );

    $form['sms_mobio_service1_usage'] = array(
      '#type' => 'markup',
      '#markup' => $config['sms_mobio_service1_usage'],
    );

    $form['sms_mobio_service1_linktopage'] = array(
      '#type' => 'markup',
      '#markup' => l($config['sms_mobio_service1_linktopage_text'], $config['sms_mobio_service1_linktopage_link']),
    );

    $form['sms_mobio_code'] = array(
      '#type' => 'textfield',
      '#title' => t('SMS Code'),
      '#size' => 6,
      '#description' => t('Enter received sms code here.')
    );

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    );

    return $form;
    //parent::buildForm($form, $form_state, $config);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state['values']['sms_mobio_code']) < 5) {
      $form_state->setErrorByName('sms_mobio_code', t('The code is too short. Try again.'));
    }

    //parent::validateForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Your sent @code code on @servid service.', array('@servid' => $form_state['values']['sms_mobio_service1_servid'], '@code' => $form_state['values']['sms_mobio_code'])));

    //
    $check = mobio_checkcode($form_state['values']['sms_mobio_service1_servid'], $form_state['values']['sms_mobio_code'], 0);

    if ($check) {
      global $user;
      $message = t('Valid code.');
      $type = 'status';
      $ip = $_SERVER['REMOTE_ADDR'];
      /*
      if ($user -> uid == 0) {
        $user -> sid = session_id();
      }

      db_insert('sms_mobio')
        ->fields(array(
          'uid' => $user -> uid,
          'sess_id' => $user -> sid,
          'ip' => $ip,
          'time' => time(),
          'sms_code' => $form_state['values']['code'],
          'serv_id' => $form_state['values']['serv_id'],
        ))
      ->execute();

      rules_invoke_event('sms_code_insert', $user, array($form_state['values']['code']), array($form_state['values']['serv_id']));
      */
      watchdog('sms_mobio',t('A Member !uid sent proper code !code on mobio.bg service !serv_id', array('!uid' => $user -> name, '!code' => $form_state['values']['sms_mobio_code'], '!serv_id' => $form_state['values']['sms_mobio_service1_servid'])));
      
      drupal_set_message(t('!url', array('!url' => l('See "SMS Mobio" module Instalation instructions.','http://d8test.d-support.eu/node/1'))));
    }
    else {

      $message = t('Wrong code');
      $type = 'error';
    }

    drupal_set_message(t('SMS Mobio: @message', array('@message' => $message)), $type, $repeat = FALSE);

  }
}