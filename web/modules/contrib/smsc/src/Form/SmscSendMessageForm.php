<?php

/**
 * @file
 */

namespace Drupal\smsc\Form;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\smsc\Smsc\DrupalSmsc;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class SmscSendMessageForm.
 */
class SmscSendMessageForm extends FormBase {

  /**
   * @var \Drupal\smsc\Smsc\DrupalSmsc
   */
  protected $drupalSmsc;

  /**
   * @var null|\Smsc\Settings\Settings
   */
  protected $settings;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $smscConfig;

  /**
   * Has account settings.
   *
   * @var \Drupal\smsc\Smsc\DrupalSmsc
   */
  protected $hasSettings;

  /**
   * Constructs a new SmscSendMessageForm object.
   *
   * @param \Drupal\smsc\Smsc\DrupalSmsc $drupalSmsc
   */
  public function __construct(DrupalSmsc $drupalSmsc) {
    $this->drupalSmsc  = $drupalSmsc;
    $this->settings    = $this->drupalSmsc->getSettings();
    $this->smscConfig  = $this->drupalSmsc->getConfig();
    $this->hasSettings = $this->settings->valid();
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('smsc')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smsc_send_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->smscConfig;

    $form['#attached']['library'][] = 'smsc/smsc';

    $form['messages-wrapper'] = [
      '#type'       => 'container',
      '#attributes' => [
        'id'    => 'messages-wrapper',
        'class' => ['messages-wrapper'],
      ],
      '#weight'     => -100,
    ];

    if ($this->hasSettings) {

      $form['message-form'] = [
        '#type'       => 'container',
        '#attributes' => ['class' => ['message-form']],
      ];

      $form['message-form']['phones'] = [
        '#type'        => 'textfield',
        '#title'       => $this->t('Phones'),
        '#description' => $this->t('Phone number[s]'),
        '#required'    => TRUE,
      ];

      $form['message-form']['message'] = [
        '#type'        => 'textarea',
        '#title'       => $this->t('Message'),
        '#description' => $this->t('Message body'),
        '#required'    => TRUE,
      ];

      $form['message-form']['options'] = [
        '#type'       => 'container',
        '#attributes' => ['class' => ['message-form--options']],
        '#tree'       => TRUE,
      ];

      $form['message-form']['options']['translit'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Transliterate'),
        '#description'   => $this->t('Transliterate message'),
        '#default_value' => $config->get('translit'),
      ];

      $form['message-form']['options']['sender'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Sender ID'),
        '#description'   => $this->t('Choose Sender ID'),
        '#options'       => $this->drupalSmsc->getSenders(),
        '#size'          => 0,
        '#default_value' => $config->get('sender'),
      ];

      $form['message-form']['submit'] = [
        '#type'  => 'submit',
        '#value' => $this->t('Submit'),
        '#ajax'  => [
          'callback' => '::ajaxSubmitCallback',
          'event'    => 'click',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $form['info'] = [
        '#type'       => 'container',
        '#attributes' => [
          'id'    => 'smsc-info',
          'class' => ['message-form'],
        ],
      ];

      $form['info']['balance'] = [
        '#type'       => 'container',
        '#attributes' => [
          'id'    => 'smsc-info--balance',
          'class' => ['smsc-info--balance'],
        ],
      ];

      $form['info']['balance']['title'] = [
        '#markup' => $this->t('Balance') . ': ',
      ];

      $form['info']['balance']['amount'] = [
        '#type'       => 'html_tag',
        '#tag'        => 'span',
        '#value'      => $this->drupalSmsc->getBalanceAmount(),
        '#attributes' => [
          'id'    => 'smsc-info--balance--amount',
          'class' => ['smsc-info--balance--amount'],
        ],
      ];

      $form['info']['balance']['currency'] = [
        '#markup' => $this->drupalSmsc->getBalanceCurrency(),
      ];
    }
    else {
      $settingsLink = Url::fromRoute('smsc.smsc_settings')->toString();

      drupal_set_message(t('You need <a href=":url">autorize</a> before send any message!', [':url' => $settingsLink]), 'warning');

      $form['no-settings'] = [
        '#type'       => 'container',
        '#attributes' => ['class' => ['no-settings']],
      ];

      $form['no-settings']['markup'] = [
        '#markup' => $this->t('<a href=":url">Set up</a> SMSC-account first.', [':url' => $settingsLink]),
      ];
    }

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
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $message       = [
      '#theme'           => 'status_messages',
      '#message_list'    => drupal_get_messages(),
      '#status_headings' => [
        'status'  => t('Status message'),
        'error'   => t('Error message'),
        'warning' => t('Warning message'),
      ],
    ];
    $messages      = \Drupal::service('renderer')->render($message);
    $ajax_response->addCommand(new HtmlCommand('#messages-wrapper', $messages));

    $newBalance = $form_state->getValue('balance');
    if (isset($newBalance)) {
      $ajax_response->addCommand(new HtmlCommand('#smsc-info--balance--amount', $newBalance));
    }

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    $values = $form_state->getValues();

    /**
     * Extract variables from array.
     *
     * @var string $phones
     * @var string $message
     * @var array  $options
     */
    extract($values);

    $options['cost'] = 3;

    // Send SMS
    $sms      = $this->drupalSmsc::sendSms($phones, $message, $options);
    $response = $sms->results();

    if (isset($response->balance)) {
      $balance = round($response->balance, 2);
      \Drupal::cache()->set('smsc:balance:amount', $balance, (time() + 60));
      $form_state->setValue('balance', $balance);
    }

    if (!isset($response->error)) {
      drupal_set_message($this->t('Message sent success.'));

      if (isset($response->cost)) {
        drupal_set_message($this->t('Message cost: :amount :currency.', [
          ':amount'   => round($response->cost, 2),
          ':currency' => $this->drupalSmsc->getBalanceCurrency(),
        ]));
      }
    }
    else {
      drupal_set_message($this->t("Message failed! :error", [
        ':error' => $sms->getData()->getStatusCodeMessage(),
      ]), 'error');
    }
  }
}
