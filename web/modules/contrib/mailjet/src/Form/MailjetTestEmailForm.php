<?php
/**
 *  * @file
 *  * Contains \Drupal\mailjet\Form\MailjetTestEmailForm.
 *  */

namespace Drupal\mailjet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Utility\SafeMarkup;

class MailjetTestEmailForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mailjet_test_email.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config.mailjet_test_email';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $config = \Drupal::config('system.site');
    $form['test_email'] = [
      '#type' => 'textfield',
      '#title' => t('Recipient of test mail '),
      '#default_value' => $config->get('mail'),
      '#description' => t('Mailjet is using the Site Email address for sending emails (located in Configuration → System → Basic Site Settings). Make sure you have validated this address in your <a href="https://app.mailjet.com/account/sender" target="_blank">Mailjet account</a>'),
    ];


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Test Message'),
      '#button_type' => 'primary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (!valid_email_address($form_state->getValue('test_email'))) {
      $form_state->setErrorByName('test_email', t('The provided test e-mail address is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_mailjet = $this->config('mailjet.settings');

    if (!empty($config_mailjet->get('mailjet_active'))
      && !empty($config_mailjet->get('mailjet_username'))
      && !empty($config_mailjet->get('mailjet_password'))) {

      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'mailjet';
      $key = 'test_mail';
      $to = $form_state->getValue('test_email');
      $params['message'] = t('Your Mailjet configuration is ok!');
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $send = TRUE;
      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

      if ($result['result'] !== TRUE) {
        drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
      }
      else {
        drupal_set_message(t('Your message has been sent.'));
      }

    }
    else {
      drupal_set_message(t('There was a problem with configuration with Mailjet API. Please enter API keys and other information again!'), 'error');
    }
  }
}
