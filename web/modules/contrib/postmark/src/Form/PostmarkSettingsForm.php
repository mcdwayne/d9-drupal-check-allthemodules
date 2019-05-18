<?php

namespace Drupal\postmark\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\postmark\PostmarkHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Postmark settings.
 */
class PostmarkSettingsForm extends ConfigFormBase {

  /**
   * The core mail manager service.
   *
   * @var \Drupal\postmark\PostmarkHandler
   */
  protected $postmarkHandler;

  /**
   * Constructs a Postmark settings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\postmark\PostmarkHandler $postmark_handler
   *   The core mail manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PostmarkHandler $postmark_handler) {
    parent::__construct($config_factory);

    $this->postmarkHandler = $postmark_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('postmark.mail_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'postmark_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['postmark.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The user's Postmark API key
    $form['postmark_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postmark API Token'),
      '#default_value' => $this->config('postmark.settings')->get('postmark_api_key'),
      '#description' => t('The Server API token similar to ed742D75-5a45-49b6-a0a1-5b9ec3dc9e5d, generated on the Postmark Server Credentials page.'),
      '#required' => TRUE,
    ];
    $form['postmark_sender_signature'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postmark Sender Signature'),
      '#default_value' => $this->config('postmark.settings')->get('postmark_sender_signature'),
      '#description' => t('The email address configured within Postmark as the Sender Signature for the server associated with the Server API token.'),
      '#required' => TRUE,
    ];
    $form['debug'] = [
      '#type' => 'details',
      '#title' => $this->t('Debugging'),
      '#open' => FALSE,
    ];
    $form['debug']['postmark_debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Postmark debugging'),
      '#default_value' => $this->config('postmark.settings')->get('postmark_debug_mode'),
    ];
    $form['debug']['postmark_debug_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enable Postmark debugging email'),
      '#default_value' => $this->config('postmark.settings')->get('postmark_debug_email'),
      '#description' => $this->t('Use a debugging email, so all system emails will go to this address. Debugging mode must be on for this to work'),
    ];
    $form['debug']['postmark_debug_no_send'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Postmark debugging to not send an email and therefore not use a credit'),
      '#default_value' => $this->config('postmark.settings')->get('postmark_debug_no_send'),
    ];
    $form['test'] = [
      '#type' => 'details',
      '#title' => $this->t('Test email'),
      '#open' => FALSE,
    ];
    $form['test']['test_address'] = [
      '#type' => 'textfield',
      '#title' => t('Recipient'),
      '#default_value' => '',
      '#description' => $this->t('Enter a valid email address to send a test email.'),
    ];
  
    return parent::buildForm($form, $form_state);
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
    $config = $this->config('postmark.settings');
    $config
      ->set('postmark_api_key', $form_state->getValue('postmark_api_key'))
      ->set('postmark_sender_signature', $form_state->getValue('postmark_sender_signature'))
      ->set('postmark_debug_mode', $form_state->getValue('postmark_debug_mode'))
      ->set('postmark_debug_email', $form_state->getValue('postmark_debug_email'))
      ->set('postmark_debug_no_send', $form_state->getValue('postmark_debug_no_send'))
      ->save();

    // If an address is submitted, send a test email message.
    $test_address = $form_state->getValue('test_address');
    if ($test_address) {
      $message = $this->t('A test e-mail has been sent to @mail.  You may want to check the logs for any error messages.', [
        '@mail' => $test_address,
      ]);

      drupal_set_message($message, 'warning');
  
      $postmark_message = [
        'from' => $form_state->getValue('postmark_sender_signature'),
        'to' => $test_address,
        'subject' => $this->t('Test message from Postmark'),
        'text' => $this->t('Just testing.'),
      ];

      $this->postmarkHandler->sendMail($postmark_message);
    }

    parent::submitForm($form, $form_state);
  }

}
