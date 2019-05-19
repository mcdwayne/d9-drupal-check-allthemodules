<?php

namespace Drupal\sparkpost\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sparkpost\ClientService;

/**
 * Class TestMailForm.
 *
 * @package Drupal\sparkpost\Form
 */
class TestMailForm extends FormBase {

  /**
   * Drupal\sparkpost\ClientService definition.
   *
   * @var \Drupal\sparkpost\ClientService
   */
  protected $sparkpostClient;

  /**
   * Drupal\Core\Mail\MailManagerInterface definition.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientService $sparkpostClient, MailManagerInterface $mailManager) {
    $this->sparkpostClient = $sparkpostClient;
    $this->mailManager = $mailManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sparkpost.client'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_mail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['recipient'] = [
      '#type' => 'email',
      '#title' => $this->t('Recipient'),
      '#required' => TRUE,
      '#default_value' => $this->configFactory()->get('system.site')->get('mail'),
    ];
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 255,
      '#default_value' => $this->t('Drupal Sparkpost test email'),
    ];
    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $this->t('If you receive this message it means your site is capable of using Sparkpost to send email. This url is here to test click tracking: <a href=":link">link</a>', [
        ':link' => Url::fromUri('http://www.drupal.org/project/sparkpost')->toString(),
      ]),
    ];
    $form['attachment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add attachment'),
      '#default_value' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Send'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Prepare.
    $to = $form_state->getValue('recipient');
    $params = [];
    $params['subject'] = $form_state->getValue('subject');
    $params['body'] = $form_state->getValue('body');
    $params['include_attachment'] = $form_state->getValue('attachment');
    // Send.
    $message = $this->mailManager->mail('sparkpost', 'test_mail_form', $to, LanguageInterface::LANGCODE_NOT_SPECIFIED, $params);
    if ($message['result']) {
      drupal_set_message($this->t('Sparkpost test email sent to %to.', [
        '%to' => $to,
      ]), 'status');
    }
    else {
      $form_state->setRebuild();
    }
  }

  /**
   * Access handler for the form.
   *
   * Requires a user to set an api key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result.
   */
  public function access(AccountInterface $account) {
    $config = \Drupal::config('sparkpost.settings');
    if ($config->get('api_key')) {
      return AccessResult::allowedIfHasPermission($account, 'administer sparkpost');
    }
    return AccessResult::forbidden();
  }

}
