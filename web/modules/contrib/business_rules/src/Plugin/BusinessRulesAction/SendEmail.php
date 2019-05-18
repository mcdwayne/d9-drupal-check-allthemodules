<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\language\ConfigurableLanguageManagerInterface;

/**
 * Class SendEmail.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "send_email",
 *   label = @Translation("Send email"),
 *   group = @Translation("System"),
 *   description = @Translation("Sent email action."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class SendEmail extends BusinessRulesActionPlugin {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id = 'send_email', $plugin_definition = []) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $this->util->container->get('plugin.manager.mail');
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $form_state->set('business_rules_item', $item);

    // Only show settings form if the item is already saved.
    if ($item->isNew()) {
      return [];
    }

    $settings['subject'] = [
      '#type'          => 'textfield',
      '#title'         => t('Subject'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('subject'),
      '#desctiption'   => t('Mail subject'),
    ];

    $site_mail = \Drupal::config('system.site')->get('mail');

    $settings['use_site_mail_as_sender'] = [
      '#type'          => 'select',
      '#title'         => t('Use site mail as sender'),
      '#options'       => [
        TRUE  => t('Yes'),
        FALSE => t('No'),
      ],
      '#required'      => TRUE,
      '#default_value' => ($item->getSettings('use_site_mail_as_sender') === FALSE) ? FALSE : TRUE,
      '#description'   => t('Use %mail as sender', ['%mail' => $site_mail]),
    ];

    $settings['from'] = [
      '#type'          => 'textfield',
      '#title'         => t('From'),
      '#default_value' => $item->getSettings('from'),
      '#description'   => t('You can use variables on this field.'),
      '#states'        => [
        'visible' => [
          'select[name="use_site_mail_as_sender"]' => ['value' => '0'],
        ],
      ],
    ];

    $settings['to'] = [
      '#type'          => 'textfield',
      '#title'         => t('To'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('to'),
      '#description'   => t('For multiple recipients, use semicolon(;). You can use variables on this field. The variable can contain one email or an array of emails'),
    ];

    $settings['subject'] = [
      '#type'          => 'textfield',
      '#title'         => t('Subject'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('subject'),
      '#description'   => t('You can use variables on this field.'),
    ];

    $settings['format'] = [
      '#type'          => 'select',
      '#title'         => t('Mail format'),
      '#options'       => [
        'html' => t('HTML'),
        'text' => t('Text'),
      ],
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('format') ? $item->getSettings('format') : 'text',
      '#description'   => t('Email body format.'),
    ];

    $settings['body'] = [
      '#type'          => 'text_format',
      '#title'         => t('Message'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('body')['value'],
      '#description'   => t('You can use variables on this field.'),
      '#format' => ($item->getSettings('body') && isset($item->getSettings('body')['format'])) ? $item->getSettings('body')['format'] : 'full_html',
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {

    if (isset($settings['use_site_mail_as_sender']) && $settings['use_site_mail_as_sender'] === 1) {
      $settings['from'] = NULL;
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {

    $event_variables = $event->getArgument('variables');
    $query_service   = \Drupal::getContainer()->get('entity.query');
    $to              = $this->processVariables($action->getSettings('to'), $event_variables);
    $arr_to          = explode(';', $to);
    $result          = [];
    $email_validator = \Drupal::getContainer()->get('email.validator');

    if ($action->getSettings('use_site_mail_as_sender')) {
      $from = \Drupal::config('system.site')->get('mail');
    }
    else {
      $from = $action->getSettings('from');
      $from = $this->processVariables($from, $event_variables);
    }

    foreach ($arr_to as $to) {
      // Check if it's a valid email address.
      if (!$email_validator->isValid($to)) {
        continue;
      }

      // Check if $to is an email registered on database.
      /** @var \Drupal\Core\Entity\Query\Sql\Query $query */
      $query = $query_service->get('user');
      $query->condition('mail', $to);
      $ids = $query->execute();

      $entityManager = \Drupal::entityTypeManager()->getStorage('user');
      $users         = $entityManager->loadMultiple($ids);

      // If email address is duplicated on user table, use the first email to
      // get the user language.
      if (count($users)) {
        foreach ($users as $user) {
          $langcode = $user->language()->getId();
          break;
        }

      }
      else {
        // If user not found, use the site language.
        $langcode = \Drupal::config('system.site')->get('langcode');
      }

      // Send the email.
      $languageManager = \Drupal::languageManager();
      if ($languageManager instanceof ConfigurableLanguageManagerInterface) {
        $action_translated   = $languageManager->getLanguageConfigOverride($langcode, 'business_rules.action.' . $action->id());
        $settings_translated = $action_translated->get('settings');
      }

      $subject = isset($settings_translated['subject']) ? $settings_translated['subject'] : $action->getSettings('subject');
      $message = isset($settings_translated['body']) ? $settings_translated['body'] : $action->getSettings('body')['value'];
      $subject = $this->processVariables($subject, $event_variables);
      $message = $this->processVariables($message, $event_variables);

      // Check if body is on html format.
      if ($action->getSettings('format') == 'html') {
        $headers = ['Content-Type' => 'text/html; charset=UTF-8'];
        $message = html_entity_decode($message);
      }
      else {
        $headers = ['Content-Type' => 'text/plain; charset=UTF-8'];
        $message = MailFormatHelper::htmlToText($message);
      }

      $params = [
        'headers' => $headers,
        'from'    => $from,
        'subject' => $subject,
        'message' => $message,
      ];

      $send_result = $this->mailManager->mail('business_rules', 'business_rules_mail', $to, $langcode, $params, $from);

      $result = [
        '#type'   => 'markup',
        '#markup' => t('Send mail result: %result. Subject: %subject, from: %from, to: %to, message: %message.', [
          '%result'  => $send_result['result'] ? t('success') : t('fail'),
          '%subject' => $subject,
          '%from'    => $from,
          '%to'      => implode('; ', $arr_to),
          '%message' => $message,
        ]),
      ];
    }

    return $result;
  }

}
