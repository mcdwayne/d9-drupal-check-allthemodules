<?php

/**
 * @file
 * Contains \Drupal\saml_sp\Form\GovDeliveryTestMessagesForm.
 */

namespace Drupal\govdelivery\Form;

use Drupal\govdelivery\Plugin\Mail\GovDeliveryMailSystem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Url;
use Drupal\Core\Link;

class GovDeliveryTestMessagesForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'govdelivery_test_messages';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $recipients = $email = $form_state->getValues()['email'];

    // Send message.
    if (isset($recipients) && !empty($recipients)) {
      $this->send_test_message($recipients);
      /*
      $from = $postinfo['values']['govdelivery_test_settings']['from'];
      // Pass on the list to be sent.
      if (!empty($from)) {
        $status = govdelivery_send_test_message($recipients, $from);
      }
      else {
        $status = govdelivery_send_test_message($recipients);
      }
      $mailManager = \Drupal::service('plugin.manager.mail')->mail('example', 'notice', $account->mail, $langcode, $params);/**/
      //$params = [];
      //$account = \Drupal::currentUser();
      //$mailManager = \Drupal::service('plugin.manager.mail')->mail('govdelivery', 'test_message', $recipients, $account->getPreferredLangcode(), $params);
    }

  }
  
  /**
   * Initiate the sending of the test message.
   */
  function send_test_message($recipients) {
    $mail_config = $this->configFactory->getEditable('system.mail');
    $gdConfig = \Drupal::config('govdelivery.tms_settings');
    $account = \Drupal::currentUser();
    // If module is off, send the test message with GovDelivery by temporarily overriding.
    if (!$gdConfig->get('enabled')) {
      $original = $mail_config->get('interface');
      $mail_system = 'GovDeliveryMailSystem';
      $mail_config->set('interface.default', $mail_system)->save();
    }
    \Drupal::service('plugin.manager.mail')->mail('govdelivery', 'test_message', $recipients, $account->getPreferredLangcode(), $params);
    if (!$gdConfig->get('enabled')) {
      $mail_config->set('interface', $original)->save();
    }
    drupal_set_message(t('A test e-mail has been sent to @email via GovDelivery. You may want to check the log for any error messages.', ['@email' => $recipients]));
  }

  /**
   * {@inheritdoc}
   */
  /*
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form = [], FormStateInterface $form_state) {
    $config = $this->config('govdelivery.settings');
    $form['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email address to send messages to.'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Send test message'),
    );

    return $form;
  }
}
