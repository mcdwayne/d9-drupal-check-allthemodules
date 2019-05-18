<?php

namespace Drupal\mailjet_api\Form;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mailjet_api\MailjetApiHandler;

/**
 * Class MailjetApiTestEmailForm.
 *
 * @package Drupal\mailjet_api\Form
 */
class MailjetApiTestEmailForm extends FormBase {

  /**
   * Drupal\mailjet_api\MailjetApiHandler definition.
   *
   * @var \Drupal\mailjet_api\MailjetApiHandler
   */
  protected $mailjetApiHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Mail Manager.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * MailjetApiTestEmailForm constructor.
   *
   * @param \Drupal\mailjet_api\MailjetApiHandler $mailjet_api_handler
   *   The Mailjet API Handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user proxy.
   * @param \Drupal\Core\Mail\MailManager $mailManager
   *   The mail manager service.
   * @param \Drupal\Core\File\FileSystem $fileSystem
   *   The file system service.
   */
  public function __construct(MailjetApiHandler $mailjet_api_handler, AccountProxyInterface $current_user, MailManager $mailManager, FileSystem $fileSystem) {
    $this->mailjetApiHandler = $mailjet_api_handler;
    $this->currentUser = $current_user;
    $this->mailManager = $mailManager;
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mailjet_api.mail_handler'),
      $container->get('current_user'),
      $container->get('plugin.manager.mail'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailjet_api_test_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    MailjetApiHandler::status(TRUE);
    // TODO: Show current mail system to make sure that Mailjet is enabled.
    // But we can test all mail systems with this form.
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Test mode'),
      '#options' => [
        'mailjet' => $this->t('Mailjet'),
        'mail_manager' => $this->t('Mail Manager'),
      ],
      '#required' => TRUE,
      '#default_value' => 'mailjet',
      '#description' => $this->t('Choose which mode to test: Mailjet to test directly the module, MailManager to test the Drupal Mail Manager and the integration of Mailjet With Mail system.'),
    ];

    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From'),
      '#description' => $this->t('Email will be sent from this address. Leave empty to use the site mail.'),
    ];

    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#required' => TRUE,
      '#description' => $this->t('Email will be sent to this address.'),
      '#default_value' => $this->currentUser->getEmail(),
    ];

    $form['subject'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Subject'),
      '#required' => TRUE,
      '#default_value' => 'Mailjet API test',
    ];

    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
      '#default_value' => 'If this e-mail is displayed correctly and delivered sound and safe, congrats! You have successfully configured Mailjet API.',
    ];

    $form['include_attachment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include attachment'),
      '#description' => $this->t('If checked, an image will be included as an attachment with the test e-mail.'),
    ];

    $form['extra'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional parameters'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => $this->t('You may test more parameters to make sure they are working.'),
    ];
    $form['extra']['reply_to'] = [
      '#type' => 'email',
      '#title' => $this->t('Reply-To'),
    ];
    $form['extra']['cc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CC'),
      '#description' => $this->t('You can use commas to separate multiple recipients.'),
    ];
    $form['extra']['bcc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BCC'),
      '#description' => $this->t('You can use commas to separate multiple recipients.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('mailjet_api.admin_settings_form'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $to = $values['to'];
    $from = !empty($values['from']) ? $values['from'] : $this->config('system.site')->get('mail');

    $params = [
      'subject' => $values['subject'],
      'body' => [
        $values['body'],
      ],
    ];

    if (!empty($values['include_attachment'])) {
      $params['params']['attachments'][] = $this->fileSystem->realpath('core/misc/druplicon.png');
    }

    // Add CC / BCC values if they are set.
    if (!empty($values['cc'])) {
      $params['params']['cc'] = $values['cc'];
    }
    if (!empty($values['bcc'])) {
      $params['params']['bcc'] = $values['bcc'];
    }

    $mode = $values['mode'];
    if ($mode == 'mailjet') {
      $message = $params;
      $message['to'] = $to;
      $message['from'] = $from;
      if (!empty($values['reply_to'])) {
        $message['reply-to'] = $values['reply_to'];
      }
      $body = $this->mailjetApiHandler->buildMessagesBody($message);
      $result = $this->mailjetApiHandler->sendMail($body);
    }
    else {
      $params['from'] = $from;
      $result_mail = $this->mailManager->mail('mailjet_api', 'test_form_email', $to, $this->currentUser->getPreferredLangcode(), $params, $form_state->getValue('reply_to'), TRUE);
      $result = $result_mail['result'];
    }

    if ($result == TRUE) {
      drupal_set_message($this->t('Successfully sent message to %to.', ['%to' => $to]));
    }
    else {
      drupal_set_message($this->t('Something went wrong. Please check @logs for details.', [
        '@logs' => Link::createFromRoute($this->t('logs'), 'dblog.overview')->toString(),
      ]), 'warning');
    }
  }

}
