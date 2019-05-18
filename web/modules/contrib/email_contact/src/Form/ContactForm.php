<?php

namespace Drupal\email_contact\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ContactForm extends FormBase {

  private $entity_type;
  private $entity_id;
  private $field_name;
  private $field_settings;

  public function __construct($entity_type = NULL, $entity_id = NULL, $field_name = NULL, $field_settings = NULL) {
    $this->entity_type = $entity_type;
    $this->entity_id = $entity_id;
    $this->field_name = $field_name;
    $this->field_settings = $field_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $form_id = 'email_contact_mail';
    if ($this->entity_id) {
      $form_id .= '_' . $this->entity_id;
    }
    return $form_id . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $this->entity_id = $id;
    $user = \Drupal::currentUser();
    $emails = email_contact_get_emails_from_field($this->entity_type, $this->entity_id, $this->field_name);
    $form['emails'] = array(
      '#type' => 'value',
      '#value' => serialize($emails),
    );
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#maxlength' => 255,
      '#default_value' => $user->id() ? $user->getDisplayName() : '',
      '#required' => TRUE,
    ];
    $form['mail'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Your e-mail address'),
      '#maxlength' => 255,
      '#default_value' => $user->id() ? $user->getEmail() : '',
      '#required' => TRUE,
    );
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Send e-mail'),
    );

    if (!$form_state->get('settings')) {
      $form_state->set('settings', $this->field_settings);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!valid_email_address($form_state->getValue('mail'))) {
      $form_state->setErrorByName('mail', $this->t('You must enter a valid e-mail address.'));
    }
    if (preg_match("/\r|\n/", $form_state->getValue('subject'))) {
      $form_state->setErrorByName('subject', $this->t('The subject cannot contain linebreaks.'));
      $msg = 'Email injection exploit attempted in email form subject: @subject';
      $this->logger('email_contact')->notice($msg, ['@subject' => $form_state['values']['subject']]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $emails = unserialize($form_state->getValue('emails'));
    // E-mail address of the sender: as the form field is a text field,
    // all instances of \r and \n have been automatically stripped from it.
    $reply_to = $form_state->getValue('mail');
    $settings = $form_state->get('settings');

    $params['subject'] = $form_state->getValue('subject');
    $params['name'] = $form_state->getValue('name');
    $params['default_message'] = $settings['default_message'];
    $params['message'] = '';

    if ($settings['include_values']) {
      $params['message'] .= 'Name: ' . $params['name'] . '<br/>' .
      'Email: ' . $reply_to . '<br/>';
    }
    $params['message'] .= '<br/>Message: ' . $form_state->getValue('message');

    // Send the e-mail to the recipients.
    $mailManager = \Drupal::service('plugin.manager.mail');
    $to = implode(', ', $emails);
    $module = 'email_contact';
    $key = 'contact';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, $reply_to, $send);
    if ($result['result'] !== true) {
      drupal_set_message($this->t('There was a problem sending your message and it was not sent.'), 'error');
    }
    else {
      drupal_set_message($this->t('Your message has been sent.'));
      $msg = 'Email sent from: @replyto to: @to about: "@subject" containing: "@message"';
      $this->logger('email_contact')->notice($msg, [
        '@name' => $params['name'],
        '@replyto' => $reply_to,
        '@to' => $to,
        '@subject' => $params['subject'],
        '@message' => $params['message']
      ]);
    }

    $redirect = '/';
    if (!empty($settings['redirection_to'])) {
      switch ($settings['redirection_to']) {
        case 'current':
          $redirect = NULL;
          break;

        case 'custom':
          $redirect = $settings['custom_path'];
          break;

        default:
          // TODO: $form_state['redirect'] = $path.
          break;

      }
    }
    if ($redirect) {
      $url = Url::fromUserInput($redirect);
      $form_state->setRedirectUrl($url);
    }
  }

}
