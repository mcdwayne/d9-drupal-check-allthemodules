<?php

namespace Drupal\exception_mailer\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes exception email broadcasts.
 *
 * @QueueWorker(
 *   id = "exception_email_queue",
 *   title = @Translation("Email worker: email queue"),
 *   cron = {"time" = 60}
 * )
 */
class ExceptionEmailQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * Constructs a new ExceptionEmailQueue object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   */
  public function __construct(MailManagerInterface $mail_manager) {
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $params = $data;
    $this->mailManager->mail('exception_mailer', 'notify_exception', $data['email'], 'en', $params, $send = TRUE);
  }

}
