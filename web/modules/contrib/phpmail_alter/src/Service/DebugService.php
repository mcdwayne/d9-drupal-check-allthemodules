<?php

namespace Drupal\phpmail_alter\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * PhpMailAlter DebugService service.
 */
class DebugService implements DebugServiceInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new DebugService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Log errors.
   */
  public function log($message, $mail_headers) {
    // Send error message.
    $subj = is_object($message['subject']) ? $message['subject']->render() : $message['subject'];
    $log_message = "{$message['to']} $subj <pre>$mail_headers</pre>";
    // Logs an error.
    $this->loggerFactory->get('phpmail_alter')->error($log_message);
  }

  /**
   * Debug mail system.
   */
  public function debug($message, $mail_headers, $mail_subject, $mail_body, $additional_headers) {
    // Debug mode.
    $config = $this->configFactory->get('phpmail_alter.settings');
    if ($config->get('debug')) {
      // Watchdog Log.
      $log = [
        'to' => $message['to'],
        'subject' => is_object($message['subject']) ? $message['subject']->render() : $message['subject'],
        'headers' => $mail_headers,
        'additional_headers' => $additional_headers,
      ];
      $log_message = "<pre>" . print_r($log, TRUE) . "</pre>";
      $this->loggerFactory->get('phpmail_alter')->notice($log_message);

      // Debug output.
      if ($this->moduleHandler->moduleExists('devel')) {
        $debug_info = [
          'to' => $message['to'],
          'subject' => [
            'encoded' => is_object($mail_subject) ? $mail_subject->render() : $mail_subject,
            'decoded' => is_object($message['subject']) ? $message['subject']->render() : $message['subject'],
          ],
          'body' => $mail_body,
          'headers' => $mail_headers,
          'additional_headers' => $additional_headers,
        ];
        dsm($debug_info);
      }
    }
  }

}
