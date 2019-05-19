<?php

namespace Drupal\update_runner;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles email notifications for Update Runner.
 */
class UpdateRunnerMail implements ContainerInjectionInterface {

  /**
   * UpdateRunnerMail constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Defines mail settings for the notifications.
   *
   * @param string $key
   *   Key of the generated event.
   * @param string $message
   *   Message to be sent.
   * @param array $params
   *   Params used in the message.
   */
  public function mailDefinition($key, &$message, array $params) {
    $options = [
      'langcode' => $message['langcode'],
    ];

    switch ($key) {
      case 'job_created':
        $message['from'] = $this->configFactory->get('system.site')->get('mail');
        $message['subject'] = t('Update runner job created for @processorId', ['@processorId' => $params['processor_id']], $options);
        $message['body'][] = t('The following data will be used: %job', ['%job' => $params['job_data']]);
        break;

      case 'job_completed':
        $message['from'] = $this->configFactory->get('system.site')->get('mail');
        $message['subject'] = t('Update runner job executed for @processorId', ['@processorId' => $params['processor_id']], $options);
        $message['body'][] = t('The following data got used: %job', ['%job' => $params['job_data']]);
        break;
    }

  }

}
