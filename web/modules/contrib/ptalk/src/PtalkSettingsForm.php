<?php

namespace Drupal\ptalk;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure ptalk settings.
 */
class PtalkSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\ptalk\PtalkSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ptalk_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'system.site',
      'ptalk.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('ptalk.settings');

    $form['settings'] = [
      '#type' => 'vertical_tabs',
    ];

    // Conversation page settings
    $form['ptalk_page'] = [
      '#type' => 'details',
      '#title' => $this->t('Conversation page'),
      '#weight' => 0,
      '#group' => 'settings',
    ];

    $form['ptalk_page']['ptalk_limit_participants'] = [
      '#type' => 'select',
      '#title' => t('Amount of the participants of the conversation'),
      '#default_value' => $config->get('ptalk_limit_participants', 3),
      '#description' => t('Amount of the participants of the private conversation which will be outputed on the conversation page.'),
      '#options' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5],
    ];

    $form['ptalk_page']['ptalk_messages_per_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of messages on conversation page'),
      '#options' => ['5' => 5, '10' => 10, '20' => 20, '30' => 30, '50' => 50],
      '#default_value' => $config->get('ptalk_messages_per_page'),
      '#description' => $this->t('Threads will not show more than this number of messages on a single page.'),
    ];

    // Display settings
    $form['display_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Display'),
      '#weight' => 1,
      '#group' => 'settings',
    ];

    $form['display_settings']['ptalk_display_loginmessage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inform the user about new messages on login'),
      '#default_value' => $config->get('ptalk_display_loginmessage'),
      '#description' => $this->t('This option can safely be disabled if the "New message indication" block is used instead.'),
    ];

    $form['display_settings']['ptalk_display_disabled_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inform the user on messages pages that he can not write new messages when Private Conversation is disabled'),
      '#default_value' => $config->get('ptalk_display_disabled_message'),
      '#description' => $this->t('Users can (if given the permission) disable Private Conversation which disallows writing messages to them and they can not write messages themself. If enabled, those users are informed on the relevant pages why they are not allowed to write messages.'),
    ];

    $form['display_settings']['ptalk_display_preview_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show preview button on compose form'),
      '#description' => $this->t('If checked, displays a preview button when sending new messages.'),
      '#default_value' => $config->get('ptalk_display_preview_button'),
    ];

    // Links settings
    $form['links'] = [
      '#type' => 'details',
      '#title' => $this->t('Links'),
      '#weight' => 2,
      '#group' => 'settings',
    ];

    $form['links']['ptalk_display_link_self'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display "Send this user a message" links for themself'),
      '#description' => $this->t('If enabled, each users sees that link on their own profile, comments and similiar places.'),
      '#default_value' => $config->get('ptalk_display_link_self'),
    ];

    $form['links']['ptalk_display_profile_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display link on profile pages'),
      '#description' => $this->t('If this setting is enabled, a link to send a private message will be displayed on profile pages.'),
      '#default_value' => $config->get('ptalk_display_profile_links'),
    ];

    $node_types = node_type_get_names();
    $form['links']['ptalk_link_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Display link on the selected content types'),
      '#description' => $this->t('Select which content types should display a link to send a private message to the author. By default, the link is not displayed below teasers.'),
      '#default_value' => $config->get('ptalk_link_node_types'),
      '#options' => $node_types,
    ];

    $form['links']['ptalk_display_on_teaser'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display link on teasers of the selected content types'),
      '#default_value' => $config->get('ptalk_display_on_teaser'),
    ];

    $form['links']['ptalk_display_on_comments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display links on comments of selected content types'),
      '#description' => $this->t('Also display a link to send a private message to the authors of the comments of the selected content types.'),
      '#default_value' => $config->get('ptalk_display_on_comments'),
    ];

    $form['links']['ptalk_display_on_thread'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display link on thread page'),
      '#description' => $this->t('A link to send a private message to the author of the thread on the thread page.'),
      '#default_value' => $config->get('ptalk_display_on_thread'),
    ];

    $form['links']['ptalk_display_on_messages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display links on messages on the thread page'),
      '#description' => $this->t('A link to send a private message to the authors of the messages of the thread.'),
      '#default_value' => $config->get('ptalk_display_on_messages'),
    ];

    // Deleted messages settings
    $form['flush_deleted'] = [
      '#type' => 'details',
      '#title' => $this->t('Deleted conversations and messages'),
      '#description' => $this->t('By default, deleted conversations and messages are only hidden from the user but still stored in the database. These settings control if and when deleted conversations and messages should be removed from the database.'),
      '#weight' => 3,
      '#group' => 'settings',
    ];

    $form['flush_deleted']['ptalk_flush_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flush deleted conversations and messages'),
      '#default_value' => $config->get('ptalk_flush_enabled'),
      '#description' => $this->t('Enable the flushing of deleted conversations and messages. Requires that cron is enabled.'),
    ];

    $flush_days = $config->get('ptalk_flush_days');
    $form['flush_deleted']['ptalk_flush_days'] = [
      '#type' => 'select',
      '#title' => $this->t('Flush conversations and messages after they have been deleted for more days than'),
      '#description' => $this->t('If conversations or messages are deleted more then ' . $flush_days . ' ' . \Drupal::translation()->formatPlural(in_array($flush_days, [0, 1]) ? 1 : $flush_days, 'day', 'days') . ' for all users then they and all related data will be deleted from the database.'),
      '#default_value' => $flush_days,
      '#options' => ['0' => 0, '1' => 1, '2' => 2, '5' => 5, '10' => 10, '30' => 30, '100' => 100],
      '#states' => [
        'visible' => [
          "input[name='ptalk_flush_enabled']" => ['checked' => TRUE],
        ]
      ]
    ];

    $form['flush_deleted']['ptalk_flush_max'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum number of conversations or messages to flush per cron run'),
      '#description' => $this->t('The number of the conversations or messages to flush per cron run. This limitation done for performance reason.'),
      '#default_value' => $config->get('ptalk_flush_max'),
      '#options' => ['50' => 50, '100' => 100, '200' => 200, '500' => 500],
      '#states' => [
        'visible' => [
          "input[name='ptalk_flush_enabled']" => ['checked' => TRUE],
        ]
      ]
    ];

    // Message status settings
    $form['ptalk_status'] = [
      '#type' => 'details',
      '#title' => $this->t('Message status'),
      '#group' => 'settings',
      '#weight' => 4,
    ];

    $form['ptalk_status']['ptalk_message_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Private message status'),
      '#default_value' => $config->get('ptalk_message_status'),
      '#description' => $this->t('Configure if status of the message will be displayed on the thread page.'),
    ];

    $form['ptalk_status']['ptalk_message_status_limit_recipients'] = [
      '#type' => 'select',
      '#title' => t('Amount of the recipients which will be outputed in the status string'),
      '#default_value' => $config->get('ptalk_message_status_limit_recipients', 3),
      '#description' => t('If the number of outputted recipients is less than the total number of recipients of the message, then only the recipients who read the message in the final will be displayed.'),
      '#options' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5],
      '#states' => [
        'visible' => [
          "input[name='ptalk_message_status']" => ['checked' => TRUE],
        ]
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('ptalk.settings')
      ->set('ptalk_limit_participants', $form_state->getValue('ptalk_limit_participants'))
      ->set('ptalk_display_loginmessage', $form_state->getValue('ptalk_display_loginmessage'))
      ->set('ptalk_display_disabled_message', $form_state->getValue('ptalk_display_disabled_message'))
      ->set('ptalk_display_preview_button', $form_state->getValue('ptalk_display_preview_button'))
      ->set('ptalk_flush_enabled', $form_state->getValue('ptalk_flush_enabled'))
      ->set('ptalk_flush_days', $form_state->getValue('ptalk_flush_days'))
      ->set('ptalk_flush_max', $form_state->getValue('ptalk_flush_max'))
      ->set('ptalk_messages_per_page', $form_state->getValue('ptalk_messages_per_page'))
      ->set('ptalk_display_link_self', $form_state->getValue('ptalk_display_link_self'))
      ->set('ptalk_display_profile_links', $form_state->getValue('ptalk_display_profile_links'))
      ->set('ptalk_link_node_types', $form_state->getValue('ptalk_link_node_types'))
      ->set('ptalk_display_on_teaser', $form_state->getValue('ptalk_display_on_teaser'))
      ->set('ptalk_display_on_comments', $form_state->getValue('ptalk_display_on_comments'))
      ->set('ptalk_display_on_thread', $form_state->getValue('ptalk_display_on_thread'))
      ->set('ptalk_display_on_messages', $form_state->getValue('ptalk_display_on_messages'))
      ->set('ptalk_message_status', $form_state->getValue('ptalk_message_status'))
      ->set('ptalk_message_status_limit_recipients', $form_state->getValue('ptalk_message_status_limit_recipients'))
      ->save();
  }

}
