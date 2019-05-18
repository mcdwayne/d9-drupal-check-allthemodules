<?php

namespace Drupal\inmail;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\MIME\MimeParseException;
use Drupal\inmail\MIME\MimeParserInterface;
use Drupal\inmail\Plugin\DataType\BounceData;
use Drupal\user\Entity\User;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Mail message processor using services to analyze and handle messages.
 *
 * @todo Evaluate the analysis algorithms in D7 Bounce and CiviCRM https://www.drupal.org/node/2379845
 *
 * @ingroup processing
 */
class MessageProcessor implements MessageProcessorInterface {

  /**
   * The storage for message analyzer configuration entities.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $analyzerStorage;

  /**
   * The plugin manager for analyzer plugins.
   *
   * @var \Drupal\inmail\AnalyzerManagerInterface
   */
  protected $analyzerManager;

  /**
   * The storage for message handler configuration entities.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $handlerStorage;

  /**
   * The plugin manager for handler plugins.
   *
   * @var \Drupal\inmail\HandlerManagerInterface
   */
  protected $handlerManager;

  /**
   * The injected logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * The injected parser.
   *
   * @var \Drupal\inmail\MIME\MimeParserInterface
   */
  protected $parser;

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * Constructs a new message processor.
   */
  public function __construct(EntityManagerInterface $entity_manager, AnalyzerManagerInterface $analyzer_manager, HandlerManagerInterface $handler_manager, LoggerChannelInterface $logger_channel, MimeParserInterface $parser, AccountSwitcherInterface $account_switcher) {
    $this->analyzerStorage = $entity_manager->getStorage('inmail_analyzer');
    $this->analyzerManager = $analyzer_manager;
    $this->handlerStorage = $entity_manager->getStorage('inmail_handler');
    $this->handlerManager = $handler_manager;
    $this->loggerChannel = $logger_channel;
    $this->parser = $parser;
    $this->accountSwitcher = $account_switcher;
  }

  /**
   * {@inheritdoc}
   */
  public function process($key, $raw, DelivererConfig $deliverer) {
    $event = NULL;
    $result = new ProcessorResult();
    $result->setDeliverer($deliverer);

    // Create a log event.
    if (\Drupal::moduleHandler()->moduleExists('past')) {
      $event = past_event_create('inmail', 'process', 'Incoming mail');
      $event->addArgument('deliverer', $deliverer);
      // Log the raw email message.
      if (\Drupal::config('inmail.settings')->get('log_raw_emails')) {
        $event->addArgument('email', $raw);
      }
    }

    $has_account_changed = FALSE;
    try {
      // Parse message.
      $message = $this->parser->parseMessage($raw);
      // Checks that the message complies to the RFC standard.
      if (!$message->validate()) {
        $result->log('processor', 'Message Validation failed with message %message', ['%message' => implode(', ', $message->getValidationErrors())], RfcLogLevel::ERROR);
        if ($event) {
          $event->addArgument('validation errors', $message->getValidationErrors());
          $event->setSeverity(RfcLogLevel::ERROR);
          $event->save();
        }
        return $result;
      }
      // Set event message if parsing the message passed.
      if ($event) {
        $event->setMessage('Incoming mail: ' . $message->getMessageId());
      }

      // Analyze message.

      /** @var \Drupal\inmail\DefaultAnalyzerResult $default_result */
      $default_result = $result->getAnalyzerResult();
      // Enabled analyzers will be able to update the account.
      $default_result->setAccount(User::getAnonymousUser());

      $analyzer_configs = $this->analyzerStorage->loadMultiple();
      uasort($analyzer_configs, array($this->analyzerStorage->getEntityType()->getClass(), 'sort'));
      foreach ($analyzer_configs as $analyzer_config) {
        /** @var \Drupal\inmail\Entity\AnalyzerConfig $analyzer_config */
        if ($analyzer_config->status() && $analyzer_config->isAvailable()) {
          /** @var \Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerInterface $analyzer */
          $analyzer = $this->analyzerManager->createInstance($analyzer_config->getPluginId(), $analyzer_config->getConfiguration());
          $analyzer->analyze($message, $result);
        }
      }

      foreach ($result->getAnalyzerResults() as $analyzer_result) {
        $event and $event->addArgument(get_class($analyzer_result), $analyzer_result->summarize());
      }

      // Conditionally switch to the account identified by analyzers.
      if ($default_result->isUserAuthenticated()) {
        $this->accountSwitcher->switchTo($default_result->getAccount());
        $has_account_changed = TRUE;
      }

      // Handle message.
      foreach ($this->handlerStorage->loadMultiple() as $handler_config) {
        /** @var \Drupal\inmail\Entity\HandlerConfig $handler_config */
        if ($handler_config->status() && $handler_config->isAvailable()) {
          /** @var \Drupal\inmail\Plugin\inmail\handler\HandlerInterface $handler */
          $handler = $this->handlerManager->createInstance($handler_config->getPluginId(), $handler_config->getConfiguration());
          $handler->invoke($message, $result);
        }
      }

      $result->success($key);
      $this->sendMessageReport($result, $message, $deliverer);
    }
    catch (MimeParseException $e) {
      // Set event message if parsing the message fails.
      if ($event) {
        $event->setMessage('Incoming mail, parsing failed with error: ' . $e->getMessage());
        $event->addException($e);
      }

      $result->log('processor', 'Unable to process message, parser failed with error: %error', ['%error' => $e->getMessage()], RfcLogLevel::ERROR);
    }
    finally {
      if ($has_account_changed) {
        // Switch back to a previous account.
        $this->accountSwitcher->switchBack();
      }

      // Save the log event.
      foreach ($result->readLog() as $source => $log) {
        $messages = [];
        foreach ($log as $item) {
          if ($item['severity']>=RfcLogLevel::ERROR) {
            $this->loggerChannel->log($item['severity'], $item['message'], $item['placeholders']);
          }
          // Apply placeholders.
          $messages[] = SafeMarkup::format($item['message'], $item['placeholders']);
        }
        if ($event) {
          $event->addArgument($source, $messages);
        }
      }
      if ($event) {
        $event->save();
      }
    }
    return $result;
  }

  /**
   * Sends message report after processing mail.
   *
   * Skip report if processed mail is a bounce.
   *
   * @param $result \Drupal\inmail\ProcessorResult
   *  The processor result object.
   * @param $message \Drupal\inmail\MIME\MimeMessageInterface $original
   *   Received message.
   * @param $deliverer \Drupal\inmail\Entity\DelivererConfig
   *   The Deliverer configuration that delivered the messages.
   */
  public function sendMessageReport($result, $message, $deliverer) {
    // Make sure to never reply to a bounce to avoid loops.
    /** @var \Drupal\inmail\DefaultAnalyzerResult $default_result */
    $default_result = $result->getAnalyzerResult();
    if ($default_result->hasContext('bounce')) {
      /** @var BounceData $bounce_data */
      $bounce_data = $default_result->getContext('bounce')->getContextData();
      if ($bounce_data->isBounce()) {
        return;
      }
    }

    if ($deliverer->isMessageReport()) {
      $params['result'] = $result;
      $params['original'] = $message;
      $from_addresses = [];
      foreach ($message->getFrom() as $from_address) {
        $from_addresses[] = $from_address->getAddress();
      }
      // Send only if we have From recipient(s).
      if (!empty($from_addresses)) {
        $recipients = implode(', ', $from_addresses);
        // @todo consider display name?
        $mail_manager = \Drupal::service('plugin.manager.mail');
        $mail_manager->mail('inmail', 'success', $recipients,
          \Drupal::languageManager()->getDefaultLanguage()->getId(), $params);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processMultiple(array $messages, DelivererConfig $deliverer) {
    $results = [];
    foreach ($messages as $key => $message) {
      $results[$key] = $this->process($key, $message, $deliverer);
    }
    return $results;
  }
}
