<?php

namespace Drupal\mailjet_api\Plugin\QueueWorker;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\mailjet_api\MailjetApiHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sending mails on CRON run.
 *
 * @QueueWorker(
 *   id = "mailjet_api_cron_worker",
 *   title = @Translation("Mailjet API Cron Worker"),
 *   cron = {"time" = 10}
 * )
 */
class MailjetApiCronWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Mailjet API config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $mailjetApiConfig;

  /**
   * Mailjet API Logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Mailjet API mail handler.
   *
   * @var \Drupal\mailjet_api\MailjetApiHandler
   */
  protected $mailjetApiHandler;

  /**
   * SendMailBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImmutableConfig $settings, LoggerInterface $logger, MailjetApiHandler $mailjet_api_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailjetApiConfig = $settings;
    $this->logger = $logger;
    $this->mailjetApiHandler = $mailjet_api_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get('mailjet_api.settings'),
      $container->get('logger.factory')->get('mailjet_api'),
      $container->get('mailjet_api.mail_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $result = $this->mailjetApiHandler->sendMail($data->body);

    if ($this->mailjetApiConfig->get('debug_mode')) {
      $this->logger->notice('Successfully sent message on CRON from %from to %to.',
        [
          '%from' => $data->body['Messages'][0]['From']['Email'],
          '%to' => $data->body['Messages'][0]['From']['Email'],
        ]
      );
    }

    if (!$result) {
      throw new RequeueException('Mailjet API: email did not pass through API.');
    }
  }

}
