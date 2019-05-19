<?php

namespace Drupal\support_mail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Provides a form for sending emails.
 */
class MailForm extends FormBase {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new EmailExampleGetFormPage.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('language_manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'support_mail_sendmail';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['support_mail_to'] = [
      '#title' => $this->t('Recipient'),
      '#type' => 'email',
      '#description' => $this->t('The recipient address of the email.'),
    ];

    $form['support_mail_subject'] = [
      '#title' => $this->t('Subject'),
      '#type' => 'textfield',
      '#description' => $this->t('The subject of the email.'),
    ];

    $form['support_mail_message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#description' => $this->t('The body of the email.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Send email',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('support_mail_subject') == '') {
      $form_state->setErrorByName('support_mail_subject', $this->t('Enter the subject of the email.'));
    }

    if ($form_state->getValue('support_mail_message') == '') {
      $form_state->setErrorByName('support_mail_message', $this->t('Enter the body of the email.'));
    }
  }

  /**
   * Form submission handler for station import.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $params = [
      'subject' => strip_tags($form_state->getValue('support_mail_subject')),
      'body' => strip_tags($form_state->getValue('support_mail_message')),
    ];
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $result = $this->mailManager->mail("support_mail", "support_mail", $form_state->getValue('support_mail_to'), $langcode, $params, NULL, TRUE);
    if ($result['result'] == TRUE) {
      drupal_set_message($this->t('Your message has been sent.'));
    }
    else {
      drupal_set_message($this->t('There was a problem sending your email and it was not sent.'), 'error');
    }
  }

}
