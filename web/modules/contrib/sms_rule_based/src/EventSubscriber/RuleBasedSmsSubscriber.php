<?php

namespace Drupal\sms_rule_based\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Event\SmsMessageEvent;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms_rule_based\Entity\SmsRoutingRuleset;
use Drupal\sms_rule_based\RuleBasedSmsRouter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An SMS service provider that routes SMS based on user-configured rules.
 *
 * It provides a UI for building and managing routing rules.
 */
class RuleBasedSmsSubscriber implements  EventSubscriberInterface {

  protected $smsGatewayStorage;

  protected $smsRuleStorage;

  protected $eventDispatcher;

  protected $ruleBasedRouter;

  protected $config;

  protected $logger;

  public function __construct(EventDispatcherInterface $dispatcher, EntityTypeManagerInterface $entityManager, RuleBasedSmsRouter $ruleBasedRouter, ConfigFactoryInterface $config, LoggerChannelFactoryInterface $loggerFactory) {
    $this->eventDispatcher = $dispatcher;
    $this->smsGatewayStorage = $entityManager->getStorage('sms_gateway');
    $this->smsRuleStorage = $entityManager->getStorage('sms_routing_ruleset');
    $this->ruleBasedRouter = $ruleBasedRouter;
    $this->config = $config;
    $this->logger = $loggerFactory->get('sms_rule_based');
  }

  /**
   * {@inheritdoc}
   */
  public function routeSmsMessages(SmsMessageEvent $event) {
    $routed_messages = [];
    foreach ($event->getMessages() as $sms_message) {
      $routing = $this->routeMessage($sms_message);
      $recipients = $sms_message->getRecipients();
      $stub_sms = clone $sms_message;
      $stub_sms->removeRecipients($recipients);
      $counts = []; $logger = [];
      foreach ($routing['routes'] as $gateway_id => $numbers) {
        if ($numbers) {
          if ($gateway_id === '__default__') {
            $gateway = $this->smsGatewayStorage->load($this->config->get('sms.settings')
              ->get('fallback_gateway'));
          }
          else {
            $gateway = $this->smsGatewayStorage->load($gateway_id);
          }
          $routed_sms = clone $stub_sms;
          $routed_sms
            ->addRecipients($numbers)
            ->setGateway($gateway);

          $routed_messages[] = $routed_sms;
          if (isset($counts[$gateway_id])) {
            $counts[$gateway_id] += count($numbers);
          }
          else {
            $counts[$gateway_id] = count($numbers);
          }

          // Information for logging routing routes.
          $log_args = [
              '@gateway' => $gateway->label(),
              '@count' => $counts[$gateway_id],
              '@total' => count($numbers),
            ];
          $logger[] = (string) new TranslatableMarkup('@gateway: @count of @total', $log_args);
        }
      }
      if ($logger) {
        $this->logger->info("Rule-based routing:\n@logs", ['@logs' => implode("\n", $logger)]);
      }
    }
    $event->setMessages($routed_messages);
  }

  /**
   * Uses the rule-based routing service to route recipients through SMS gateways.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *
   * @return array
   */
  protected function routeMessage(SmsMessageInterface $sms) {
    if ($this->config->get('sms_rule_based.settings')->get('enable_rule_based_routing')) {
      // Get rulesets and sort them in order so the first ruleset to match is
      // implemented.
      /** @var \Drupal\sms_rule_based\Entity\SmsRoutingRuleset[] $rulesets */
      $rulesets = $this->smsRuleStorage->loadMultiple();
      // @todo this sorting needs to be checked.
      uasort($rulesets, function(SmsRoutingRuleset $ruleset1, SmsRoutingRuleset $ruleset2) {
        $weight1 = $ruleset1->get('weight');
        $weight2 = $ruleset2->get('weight');
        return ($weight1 > $weight2) ? 1 : ($weight1 == $weight2 ? 0 : -1);
      });
      $routing = $this->ruleBasedRouter->routeSmsRecipients($sms, $rulesets);
    }
    else {
      $routing = [
        'routes' => [
          '__default__' => $sms->getRecipients(),
        ],
      ];
    }
    return $routing;
  }

  /**
   * Merges multiple SMS message results into one result.
   *
   * @param \Drupal\sms\Message\SmsMessageResultInterface[] $results
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   A single message result consisting of merger of all those in $results.
   */
  public function mergeMessageResults(array $results) {
    $data = [
      'status' => TRUE,
      'credit_balance' => 0,
      'credits_used' => 0,
      'error_message' => '',
      'reports' => [],
    ];
    foreach ($results as $sms_result) {
      $data['status'] &= $sms_result->getStatus();
      $data['credit_balance'] = $sms_result->getBalance();
      $data['credits_used'] += $sms_result->getCreditsUsed();
      $data['error_message'] = $sms_result->getErrorMessage();
      $data['reports'] += $sms_result->getReports();
    }
    return new SmsMessageResult($data);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SmsEvents::MESSAGE_PRE_PROCESS][] = ['routeSmsMessages', 1200];
    return $events;
  }
}
