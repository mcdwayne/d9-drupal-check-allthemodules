<?php

namespace Drupal\healthcheck_email\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\healthcheck\Event\HealthcheckCriticalEvent;
use Drupal\healthcheck\Event\HealthcheckCronEvent;
use Drupal\healthcheck\Event\HealthcheckEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Language\LanguageInterface;

/**
 * Send healthcheck reports via email.
 */
class EmailCheckSubscriber implements EventSubscriberInterface {

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   */
  public function __construct(MailManagerInterface $mailManager,
                              Token $tokenService,
                              ConfigFactoryInterface $configFactory) {
    $this->mailManager = $mailManager;
    $this->tokenService = $tokenService;
    $this->configFactory = $configFactory;
  }

  /**
   * Send email when a critical finding is made.
   *
   * @param \Drupal\healthcheck\Event\HealthcheckRunEvent $event
   *   The event.
   */
  public function doCritical(HealthcheckCriticalEvent $event) {
    /** @var \Drupal\healthcheck\Report\ReportInterface $report */
    $report = $event->getReport();

    // Add the report to token data.
    $token_data = [
      'healthcheck_report' => $report,
    ];

    // Get the email configuration settings.
    $config = $this->configFactory->get('healthcheck_email.settings');

    // If critical emails aren't enabled, skip further processing.
    $enabled = $config->get('email_critical_enabled');
    if (empty($enabled)) {
      return;
    }

    // Get email fields from config.
    $to = $config->get('email_critical_to');
    $subject = $config->get('email_critical_subject');
    $body = $config->get('email_critical_body');

    // Assemble the params to send the email.
    $params = [
      'subject' => $this->tokenService->replace($subject, $token_data),
      'body' => $this->tokenService->replace($body, $token_data),
    ];

    // Send it.
    $this->mailManager->mail('healthcheck_email', 'healthcheck_critical', $to, LanguageInterface::LANGCODE_NOT_SPECIFIED, $params);
  }

  /**
   * Email reports performed in the background.
   *
   * @param \Drupal\healthcheck\Event\HealthcheckRunEvent $event
   *   The event.
   */
  public function doCron(HealthcheckCronEvent $event) {
    $report = $event->getReport();

    // Add the report to token data.
    $token_data = [
      'healthcheck_report' => $report,
    ];

    // Get the email configuration settings.
    $config = $this->configFactory->get('healthcheck_email.settings');

    // If critical emails aren't enabled, skip further processing.
    $enabled = $config->get('email_cron_enabled');
    if (empty($enabled)) {
      return;
    }

    // Get email fields from config.
    $to = $config->get('email_cron_to');
    $subject = $config->get('email_cron_subject');
    $body = $config->get('email_cron_body');

    // Assemble the params to send the email.
    $params = [
      'subject' => $this->tokenService->replace($subject, $token_data),
      'body' => $this->tokenService->replace($body, $token_data),
    ];

    // Send it.
    $this->mailManager->mail('healthcheck_email', 'healthcheck_cron', $to, LanguageInterface::LANGCODE_NOT_SPECIFIED, $params);
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    return [
      HealthcheckEvents::CHECK_CRITICAL => [
        'doCritical',
      ],
      HealthcheckEvents::CHECK_CRON => [
        'doCron',
      ],
    ];
  }
}
