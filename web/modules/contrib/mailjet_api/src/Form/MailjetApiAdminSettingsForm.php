<?php

namespace Drupal\mailjet_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mailjet_api\MailjetApiHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MailjetAPiAdminSettingsForm.
 *
 * @package Drupal\mailjet_api\Form
 */
class MailjetApiAdminSettingsForm extends ConfigFormBase {

  /**
   * Mailjet API handler.
   *
   * @var \Drupal\mailjet_api\MailjetApiHandler
   */
  protected $mailjetApiHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('mailjet_api.mail_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailjetApiHandler $mailjet_api_handler) {
    parent::__construct($config_factory);

    $this->mailjetApiHandler = $mailjet_api_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailjet_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailjet_api_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (MailjetApiHandler::validateKey($form_state->getValue('api_key_public'), $form_state->getValue('api_key_secret')) === FALSE) {
      $form_state->setErrorByName('api_key', $this->t("Couldn't connect to the Mailjet API. Please check your API settings."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    MailjetApiHandler::checkLibrary(TRUE);
    $config = $this->config('mailjet_api.settings');

    $form['api_key_public'] = [
      '#title' => $this->t('Mailjet API Key public'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => $this->t('Enter your public API key.'),
      '#default_value' => $config->get('api_key_public'),
      '#attributes' => [
        'placeholder' => '1234567890abcdefghijklmnopqrstuv',
      ],
    ];

    $form['api_key_secret'] = [
      '#title' => $this->t('Mailjet API Key secret'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => $this->t('Enter your secret API key.'),
      '#default_value' => $config->get('api_key_secret'),
      '#attributes' => [
        'placeholder' => '1234567890abcdefghijklmnopqrstuv',
      ],
    ];

    $form['debug_mode'] = [
      '#title' => $this->t('Enable Debug Mode'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('debug_mode'),
      '#description' => $this->t('Enable to log every email and queuing.'),
    ];

    $form['sandbox_mode'] = [
      '#title' => $this->t('Enable Sandbox Mode'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('sandbox_mode'),
      '#description' => $this->t('Mailjet will accept the message but will not send it. This is useful for testing purposes.'),
    ];

    $form['information'] = [
      '#type' => 'details',
      '#title' => $this->t('Informations'),
      '#open' => TRUE,
    ];

    $form['information']['name'] = [
      '#markup' => $this->t('<p>The modules sending emails are responsible to set the sender email. By default the site name is used as the sender Name parameter. You can override it by setting the sender Name with the $messages[\'params\'][\'FromName\'] variable with an hook_mail_alter() implementation.</p>'),
    ];

    $form['advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $options = [
      '' => $this->t('None'),
    ];
    $filter_formats = filter_formats();
    foreach ($filter_formats as $filter_format_id => $filter_format) {
      $options[$filter_format_id] = $filter_format->label();
    }
    $form['advanced_settings']['format_filter'] = [
      '#title' => $this->t('Format filter'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $config->get('format_filter'),
      '#description' => $this->t('Format filter to use to render the message'),
    ];

    $form['advanced_settings']['use_theme'] = [
      '#title' => $this->t('Use theme'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('use_theme'),
      '#description' => $this->t('Enable to pass the message through a theme function. Default "mailjet" or pass one with $message["params"]["theme"]'),
    ];

    $form['advanced_settings']['embed_image'] = [
      '#title' => $this->t('Embed image'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('embed_image'),
      '#description' => $this->t('Enable this option to embed images into the mail sent (only if the Mailjet API format plugin Mail is used to format the outgoing mail).'),
    ];

    $form['advanced_settings']['use_queue'] = [
      '#title' => $this->t('Enable Queue'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('use_queue'),
      '#description' => $this->t('Enable to queue mails and send them out in background by cron'),
    ];

    $form['advanced_settings']['custom_campaign'] = [
      '#title' => $this->t('Enable Custom Campaign'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('custom_campaign'),
      '#description' => $this->t('Enable to tag mails with a custom campaign ID'),
    ];

    $form['advanced_settings']['custom_campaign_info'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="custom_campaign"]' => ['checked' => TRUE],
        ]
      ],
    ];
    $form['advanced_settings']['custom_campaign_info']['custom_campaign_info_help'] = [
      '#markup' => $this->t('To set a custom campaign id, module should provide this id in a hook_mail() or hook_mail_alter() implementation into the message parameter : <b>$message[\'params\'][\'CustomCampaign\']</b>'),
    ];

    $form['advanced_settings']['deduplicate_campaign'] = [
      '#title' => $this->t('Enable Deduplicate Campaign'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('deduplicate_campaign'),
      '#description' => $this->t('Enable this option to stop contacts from being emailed several times in the same campaign. <b>Warning :</b>Use this parameter carefully if you use custom campaign id based on the the module or the key.'),
    ];

    $form['advanced_settings']['deduplicate_campaign_info'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="deduplicate_campaign"]' => ['checked' => TRUE],
        ]
      ],
    ];
    $form['advanced_settings']['deduplicate_campaign_info']['deduplicate_campaign_info_help'] = [
      '#markup' => $this->t('To enable deduplicate feature, module should enable this explicitly in a hook_mail() or hook_mail_alter() implementation into the message parameter : <b>$message[\'params\'][\'DeduplicateCampaign\']</b> with the value <b>TRUE</b>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('mailjet_api.settings')
      ->set('api_key_public', $form_state->getValue('api_key_public'))
      ->set('api_key_secret', $form_state->getValue('api_key_secret'))
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->set('sandbox_mode', $form_state->getValue('sandbox_mode'))
      ->set('use_queue', $form_state->getValue('use_queue'))
      ->set('embed_image', $form_state->getValue('embed_image'))
      ->set('format_filter', $form_state->getValue('format_filter'))
      ->set('use_theme', $form_state->getValue('use_theme'))
      ->set('custom_campaign', $form_state->getValue('custom_campaign'))
      ->set('deduplicate_campaign', $form_state->getValue('deduplicate_campaign'))
      ->save();
    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
