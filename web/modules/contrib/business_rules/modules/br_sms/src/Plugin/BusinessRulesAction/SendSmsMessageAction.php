<?php

namespace Drupal\br_sms\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Exception\SmsException;

/**
 * Class SendSmsMessageAction.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "send_sms_message",
 *   label = @Translation("Send a SMS message"),
 *   group = @Translation("SMS Framework"),
 *   description = @Translation("Send a SMS message trough SMS framework module"),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class SendSmsMessageAction extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['phone_number'] = [
      '#type' => 'textfield',
      '#title' => t('Phone number'),
      '#description' => t('The phone number to send the SMS message. You may use variable or token to fill this information.'),
      '#default_value' => $item->getSettings('phone_number'),
      '#required' => TRUE,
    ];

    $settings['message'] = [
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#description' => t('The SMS message. You may use variable or token to fill this information.'),
      '#default_value' => $item->getSettings('message'),
      '#required' => TRUE,
    ];

    $gateways = [];
    foreach (SmsGateway::loadMultiple() as $sms_gateway) {
      $gateways[$sms_gateway->id()] = $sms_gateway->label();
    }

    $settings['gateway'] = [
      '#type' => 'select',
      '#title' => t('Gateway'),
      '#description' => t('Select a gateway to route the message.'),
      '#options' => $gateways,
      '#empty_option' => '- Select -',
      '#required' => TRUE,
      '#default_value' => $item->getSettings('gateway'),
    ];

    $settings['options'] = [
      '#type' => 'fieldset',
      '#title' => t('Options'),
    ];

    $settings['skip_queue'] = [];
    $settings['automated'] = [];
    $settings['send_on'] = [];

    $settings['options']['skip_queue'] = [
      '#type' => 'checkbox',
      '#title' => t('Force skip queue'),
      '#description' => t('Send or receive the message immediately. If the gateway-specific skip queue setting is turned on, then this option is already applied.'),
      '#default_value' => $item->getSettings('skip_queue') ?: FALSE,
    ];

    $settings['options']['automated'] = [
      '#type' => 'checkbox',
      '#title' => t('Automated'),
      '#description' => t('Flag this message as automated.'),
      '#default_value' => $item->getSettings('automated') != NULL ? $item->getSettings('automated') : TRUE,
    ];

    $settings['options']['send_on'] = [
      '#type' => 'textfield',
      '#title' => t('Send on'),
      '#description' => t('Send this message on this date. This option only applies to messages in the queue. You may use variable or token to fill this information.'),
      '#default_value' => $item->getSettings('send_on') ?: (string) new DrupalDateTime('now'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $variables = $event->getArgument('variables');
    $phone_number = $action->getSettings('phone_number');
    $phone_number = $this->processVariables($phone_number, $variables);
    $message = $action->getSettings('message');
    $message = $this->processVariables($message, $variables);
    $gateway = $action->getSettings('gateway');
    $skip_queue = $action->getSettings('skip_queue');
    $automated = $action->getSettings('automated');
    $send_on = $action->getSettings('send_on');
    $send_on = $this->processVariables($send_on, $variables);

    $message = SmsMessage::create()
      ->addRecipient($phone_number)
      ->setMessage($message)
      ->setAutomated($automated)
      ->setDirection(Direction::OUTGOING);

    $send_date = new DrupalDateTime($send_on);
    if ($send_date instanceof DrupalDateTime) {
      $message->setSendTime($send_date->format('U'));
    }

    $message->setGateway(SmsGateway::load($gateway));

    $result = [
      '#type' => 'markup',
      '#markup' => t('SMS Message could not be sent.'),
    ];

    try {
      $smsProvider = $this->util->container->get('sms.provider');
      if ($skip_queue) {
        $messages = $smsProvider->send($message);
        foreach ($messages as $m) {
          $result_m = $m->getResult();

          if ($result_m->getError()) {
            $status_message = $result_m->getErrorMessage();
            $result = [
              '#type' => 'markup',
              '#markup' => t('A problem occurred while attempting to process SMS message: (code: @code) @message', [
                '@code' => $result_m->getError(),
                '@message' => $status_message,
              ]),
            ];
          }
          elseif (count($result_m->getReports())) {
            $result = [
              '#type' => 'markup',
              '#markup' => t('SMS message was processed, @count delivery reports were generated.', [
                '@count' => count($result_m->getReports()),
              ]),
            ];
          }
          else {
            $result = [
              '#type' => 'markup',
              '#markup' => t('An unknown error occurred while attempting to process SMS message. No result or reports were generated by the gateway.'),
            ];
          }
        }
      }
      else {
        $smsProvider->queue($message);
        $result = [
          '#type' => 'markup',
          '#markup' => t('SMS message added to the outgoing queue.'),
        ];
      }
    }
    catch (SmsException $e) {
      $result = [
        '#type' => 'markup',
        '#markup' => t('SMS message could not be sent: @error', [
          '@error' => $e->getMessage(),
        ]),
      ];
    }

    return $result;
  }

}
