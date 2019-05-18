<?php

namespace Drupal\mass_contact\Form;

use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\mass_contact\MassContactInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Email body settings form.
 */
class EmailBodyForm extends SettingsFormBase {

  /**
   * The Mass Contact helper service.
   *
   * @var \Drupal\mass_contact\MassContactInterface
   */
  protected $massContact;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the email body form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\mass_contact\MassContactInterface $mass_contact
   *   The mass contact helper service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, MassContactInterface $mass_contact) {
    parent::__construct($config_factory);
    $this->massContact = $mass_contact;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('mass_contact')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mass_contact_email_body_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigKeys() {
    return [
      'message_format',
      'message_prefix',
      'message_suffix',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('mass_contact.settings');

    // HTML options.
    $form['html_settings'] = [
      '#type' => 'details',
      '#open' => $this->massContact->htmlSupported(),
      '#title' => $this->t('HTML Settings'),
    ];
    if ($this->massContact->htmlSupported()) {
      $format = $config->get('message_format');
      $options = array_map(function (FilterFormatInterface $filter_format) {
        return $filter_format->label();
      }, filter_formats());
      $form['html_settings']['message_format'] = [
        '#type' => 'select',
        '#title' => $this->t('The default text format'),
        '#default_value' => $format,
        '#description' => $this->t('This is the text format that will be initially selected. If you do not want to allow HTML messages, then specify a plain text text format and do not allow it to be overridden below. Keep in mind that the user sending the message may not have access to all the text formats that are available here.'),
        '#options' => $options,
      ];
    }
    else {
      $form['html_settings']['message_format'] = [
        '#type' => 'hidden',
        '#value' => 'plain_text',
      ];
      $form['html_settings']['no_mimemail'] = [
        '#type' => 'item',
        '#description' => $this->t('To use HTML email for mass contact messages, the <a href="@mime">Mime Mail</a> module or <a href="@swift">Swiftmailer</a> module is required', ['@mime' => Url::fromUri('https://www.drupal.org/project/mimemail')->toString(), '@swift' => Url::fromUri('https://www.drupal.org/project/swiftmailer')->toString()]),
      ];
    }

    // Supplemental texts that are prepended and/or appended to every message.
    $form['supplemental_texts'] = [
      '#type' => 'details',
      '#open' => $config->get('message_prefix.value') || $config->get('message_suffix.value'),
      '#title' => $this->t('Supplemental message body texts'),
      '#description' => $this->t('You may specify additional text to insert before and/or after the message text of every mass email that is sent.'),
    ];

    $form['supplemental_texts']['message_prefix'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text to be prepended to all messages'),
      '#default_value' => $config->get('message_prefix.value'),
      '#format' => $config->get('message_prefix.format'),
      '#description' => $this->t('The text you specify in this field will be added to all Mass Contact messages sent out and will be placed before the message text entered in by the sender.'),
    ];

    $form['supplemental_texts']['message_suffix'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text to be appended to all messages'),
      '#default_value' => $config->get('message_suffix.value'),
      '#format' => $config->get('message_suffix.format'),
      '#description' => $this->t('The text you specify in this field will be added to all Mass Contact messages sent out and will be placed after the message text entered in by the sender.'),
    ];
    if (!$this->massContact->htmlSupported()) {
      $form['supplemental_texts']['message_prefix']['#allowed_formats'] = ['plain_text'];
      $form['supplemental_texts']['message_suffix']['#allowed_formats'] = ['plain_text'];
    }
    if ($this->moduleHandler->moduleExists('token')) {
      $form['supplemental_texts']['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['global'],
        '#theme_wrappers' => ['form_element'],
      ];
    }

    // Attachment options.
    // @todo Port attachment options.
    // @see https://www.drupal.org/node/2867544
    return $form;
  }

}
