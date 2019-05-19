<?php

namespace Drupal\skype\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SkypeSettingsForm
 * @package Drupal\skype\Form
 */
class SkypeSettingsForm extends ConfigFormBase {

  protected $enabled;
  protected $buttonStyle;
  protected $initiateChat;

  /**
   * SkypeSettingsForm constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(\Drupal\Core\Config\ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $config = $this->config('skype.settings');
    $this->enabled = $config->get('enable_chat');
    $this->initiateChat = $config->get('initiate_chat');
    $this->buttonStyle = $config->get('button_style');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'skype_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['skype.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('skype.settings');

    $form['#prefix'] = '<div id="skype-settings-ajax-wrapper">';
    $form['#suffix'] = '</div>';

    $ajax = [
      'wrapper' => 'skype-settings-ajax-wrapper',
      'callback' => '::ajaxRefreshForm',
      'trigger_as' => ['name' => 'dummy_submit'],
    ];
    $form['enable_chat'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable chat canvas'),
      '#default_value' => $config->get('enable_chat'),
      '#description' => $this->t('You can read the documentation on the Skype chat canvas <a href="@href" target="_blank">here</a>', ['@href' => 'https://dev.skype.com/webcontrol']),
      '#ajax' => $ajax,
    ];

    $form['dummy_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#name' => 'dummy_submit',
      '#ajax' => [
        'wrapper' => 'skype-settings-ajax-wrapper',
        'callback' => '::ajaxRefreshForm',
      ],
      '#submit' => ['::dummySubmit'],
      '#attributes' => [
        'class' => ['js-hide']
      ]
    ];

    if ($this->enabled) {
      $form['message_recipient'] = [
        '#type' => 'radios',
        '#title' => $this->t('Message recipient'),
        '#options' => [
          'data-bot-id' => 'Bot',
          'data-contact-id' => $this->t('Skype user'),
        ],
        '#default_value' => $config->get('message_recipient'),
        '#description' => $this->t('Note: if the recipient is a Skype user, then users will be prompted to sign in. (Because we want to prevent users from receiving spams)'),
        '#required' => TRUE,
      ];

      $form['chat_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Bot/Skype user ID'),
        '#default_value' => $config->get('chat_id'),
        '#description' => $this->t('Depends on the option chosen in "Message Recipient"'),
        '#required' => TRUE,
      ];

      $form['initiate_chat'] = [
        '#type' => 'radios',
        '#title' => $this->t('Initiate chat'),
        '#options' => [
          'skype-button' => $this->t('Button'),
          'skype-chat' => $this->t('Chat'),
        ],
        '#default_value' => $config->get('initiate_chat'),
        '#description' => $this->t('Use a Skype button to initiate a chat or show the chat immediately'),
        '#required' => TRUE,
        '#ajax' => $ajax,
      ];

      if ($this->initiateChat == 'skype-button') {
        $form['button_settings'] = [
          '#type' => 'details',
          '#title' => $this->t('Buttons settings'),
          '#open' => TRUE,
        ];

        $form['button_settings']['button_style'] = [
          '#type' => 'select',
          '#title' => $this->t('Button style'),
          '#options' => [
            'bubble' => $this->t('Bubble'),
            'rectangle' => $this->t('Rectangle'),
            'rounded' => $this->t('Rounded'),
          ],
          '#default_value' => $config->get('button_style'),
          '#description' => $this->t('Set an out-of-the-box style for the button'),
          '#required' => TRUE,
          '#ajax' => $ajax,
        ];

        if ($this->buttonStyle == 'rectangle' || $this->buttonStyle == 'rounded') {
          $form['button_settings']['text_only'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Text only'),
            '#default_value' => $config->get('text_only'),
            '#description' => $this->t('Do not show the chat icon'),
          ];

          $form['button_settings']['button_text'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Button text'),
            '#default_value' => $config->get('button_text'),
            '#description' => $this->t('Change the label of the button, eg: Contact')
          ];

          $form['button_settings']['button_color'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Button color'),
            '#default_value' => $config->get('button_color'),
            '#description' => $this->t('Change the button color, eg: #00AFF0'),
          ];
        }
      }
      elseif ($this->initiateChat == 'skype-chat') {
        $form['chat_settings'] = [
          '#type' => 'details',
          '#title' => $this->t('Chat settings'),
          '#open' => TRUE,
        ];

        $form['chat_settings']['chat_can_collapse'] = [
          '#type' => 'checkbox',
          '#title' => 'Chat can collapse',
          '#default_value' => $config->get('chat_can_collapse'),
          '#description' => $this->t('Enables minimization functionality'),
        ];

        $form['chat_settings']['chat_can_close'] = [
          '#type' => 'checkbox',
          '#title' => 'Chat can close',
          '#default_value' => $config->get('chat_can_close'),
          '#description' => $this->t('Enables the close button'),
        ];

        $form['chat_settings']['chat_can_upload_file'] = [
          '#type' => 'checkbox',
          '#title' => 'Chat can upload file',
          '#default_value' => $config->get('chat_can_upload_file'),
          '#description' => $this->t('Enables the upload file button'),
        ];

        $form['chat_settings']['chat_enable_animation'] = [
          '#type' => 'checkbox',
          '#title' => 'Opening animation enabled',
          '#default_value' => $config->get('chat_enable_animation'),
          '#description' => $this->t('Enables the opening animation'),
        ];

        $form['chat_settings']['chat_enable_header'] = [
          '#type' => 'checkbox',
          '#title' => 'Header enabled',
          '#default_value' => $config->get('chat_enable_header'),
          '#description' => $this->t('Enables the conversation header'),
        ];
      }

      $form['page_track'] = [
        '#type' => 'details',
        '#title' => $this->t('Pages'),
        '#open' => TRUE,
      ];

      $form['page_track']['exclude_mode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Add Skype chat canvas to specific pages'),
        '#options' => [
          0 => $this->t('Every page except the listed pages'),
          1 => $this->t('The listed pages only'),
        ],
        '#default_value' => $config->get('exclude_mode'),
        '#required' => TRUE,
      ];

      $form['page_track']['exclude_pages'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Pages'),
        '#title_display' => 'invisible',
        '#default_value' => $config->get('exclude_pages'),
        '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.\", ['%blog' => '/blog', '%blog-wildcard' => '/blog/*', '%front' => '<front>']"),
        '#rows' => 10,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback to refresh form.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function ajaxRefreshForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Dummy submit callback to change rebuild form after ajax call.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function dummySubmit(array &$form, FormStateInterface $form_state) {
    $this->enabled = $form_state->getValue('enable_chat');
    if (!empty($form_state->getValue('initiate_chat'))) {
      $this->initiateChat = $form_state->getValue('initiate_chat');
    }
    if (!empty($form_state->getValue('button_style'))) {
      $this->buttonStyle = $form_state->getValue('button_style');
    }

    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('skype.settings');
    $config->set('enable_chat', $form_state->getValue('enable_chat'));
    $config->set('message_recipient', $form_state->getValue('message_recipient'));
    $config->set('chat_id', $form_state->getValue('chat_id'));
    $config->set('initiate_chat', $form_state->getValue('initiate_chat'));
    $config->set('button_style', $form_state->getValue('button_style'));
    $config->set('text_only', $form_state->getValue('text_only'));
    $config->set('button_text', $form_state->getValue('button_text'));
    $config->set('button_color', $form_state->getValue('button_color'));
    $config->set('chat_can_collapse', $form_state->getValue('chat_can_collapse'));
    $config->set('chat_can_close', $form_state->getValue('chat_can_close'));
    $config->set('chat_can_upload_file', $form_state->getValue('chat_can_upload_file'));
    $config->set('chat_enable_animation', $form_state->getValue('chat_enable_animation'));
    $config->set('chat_can_upload_file', $form_state->getValue('chat_can_upload_file'));
    $config->set('chat_enable_header', $form_state->getValue('chat_enable_header'));
    $config->set('exclude_mode', $form_state->getValue('exclude_mode'));
    $config->set('exclude_pages', $form_state->getValue('exclude_pages'));

    $config->save();

    parent::submitForm($form, $form_state);
  }
}