<?php

namespace Drupal\peytz_mail\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\peytz_mail\PeytzMailer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the SubscribeWorkers.
 */
abstract class PeytzMailSubscribeWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Logger Channel Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * PeytzMailer object.
   *
   * @var \Drupal\peytz_mail\PeytzMailer
   */
  protected $peytzMailer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, PeytzMailer $peytz_mailer) {
    $this->logger = $logger_factory->get('peytz_mail');
    $this->peytzMailer = $peytz_mailer;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('peytz_mail.peytzmailer')
    );
  }

  /**
   * Reporter log and display information about the queue.
   *
   * @param object $item
   *   The $item which was stored in the cron queue.
   */
  public function reportWork($item) {
    $this->logger->info('Peytz Mail subscription request from user with name @name,  email @email, has been processed.', [
      '@name' => isset($item->parameters['subscriber']['full_name']) ? $item->parameters['subscriber']['full_name'] :
      (isset($item->parameters['subscriber']['first_name']) ? $item->parameters['subscriber']['first_name'] . ' ' . $item->parameters['subscriber']['last_name'] : ''),
      '@email' => $item->parameters['subscriber']['email'],
    ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    $this->peytzMailer->subscribe($data->parameters);

    if ($this->peytzMailer->getResponseCode() >= 400) {
      $msg = $this->t('Peytz mail error subscribing user with Name %name, Email %email,  %error_message, %error_code', [
        '%name' => isset($data->parameters['subscriber']['full_name']) ? $data->parameters['subscriber']['full_name'] :
        (isset($data->parameters['subscriber']['first_name']) ? $data->parameters['subscriber']['first_name'] . ' ' . $data->parameters['subscriber']['last_name'] : ''),
        '%email' => $data->parameters['subscriber']['email'],
        '%error_message' => var_export($this->peytzMailer->getResponseBody(), TRUE),
        '%error_code' => $this->peytzMailer->getResponseCode(),
      ])->render();
      // If the problem is related to invalid email, release the item and move
      // on, Suspend the queue otherwise.
      if ($this->peytzMailer->getResponseCode() == 422) {
        throw new \Exception($msg);
      }
      else {
        throw new SuspendQueueException($msg);
      }
    }

    $this->reportWork($data);
  }

}
