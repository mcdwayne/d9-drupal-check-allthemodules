<?php

namespace Drupal\elastic_email\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\elastic_email\Plugin\Mail\ElasticEmailMailSystem;

class ElasticEmailSendTest extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elastic_email_send_test';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['elastic_email_test_email_to'] = [
      '#type' => 'textfield',
      '#size' => 40,
      '#title' => $this->t('Email address to send a test email to'),
      '#description' => $this->t('Enter the email address that you would like to send a test email to.'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('system.site')->get('mail'),
    ];

    $form['elastic_email_test_email_subject'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Test Email Subject'),
      '#description' => $this->t('Enter the subject that you would like to send with the test email.'),
      '#required' => TRUE,
      '#default_value' => $this->t('Elastic Email module: configuration test email'),
    ];

    $text_body = $this->t('This is a test of the Drupal Elastic Email module configuration.') .
      "\n\n" .
      $this->t('Message generated: @time', [
        '@time' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'r')
      ]);

    $form['elastic_email_test_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Test email body contents'),
      '#description' => $this->t('Enter the email body that you would like to send.'),
      '#default_value' => $text_body,
    ];

    $form['elastic_email_test_email_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send as HTML?'),
      '#description' => $this->t('Check this to send a test email as HTML.'),
      '#default_value' => FALSE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $site_mail = \Drupal::config('system.site')->get('mail');

    $to = $form_state->getValue(['elastic_email_test_email_to']);
    $subject = $form_state->getValue(['elastic_email_test_email_subject']);

    if ($form_state->getValue(['elastic_email_test_email_html'])) {
      $text_body = NULL;
      $html_body = $form_state->getValue(['elastic_email_test_email_body']);
    }
    else {
      $text_body = $form_state->getValue(['elastic_email_test_email_body']);
      $html_body = NULL;
    }

    $mail = new ElasticEmailMailSystem();
    $result = $mail->elasticEmailSend($site_mail, NULL, $to, $subject, $text_body, $html_body);

    if (isset($result['error'])) {
      // There was an error. Return error HTML.
      drupal_set_message($this->t('Failed to send a test email to %test_to. Got the following error: %error_msg', [
        '%test_to' => $to,
        '%error_msg' => $result['error'],
      ]), 'error');
    }
    else {
      // Success!
      drupal_set_message($this->t('Successfully sent a test email to %test_to', [
        '%test_to' => $to
        ]));
    }
  }

}
