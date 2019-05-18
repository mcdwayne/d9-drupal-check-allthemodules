<?php

namespace Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudActionResponder;

use Drupal\Core\Lock\NullLockBackend;
use Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudActionResponderBase;
use Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudActionResponderInterface;
use Drupal\eloqua_rest_api\Factory\ClientFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EloquaAppCloudActionResponder(
 *  id = "ActionDebugResponder",
 *  label = @Translation("Action Debug Responder"),
 *  description = "Simple synchronous content debugging tool that always returns the same HTML for every record",
 *  api = "contacts",
 *  respond = "asynchronous",
 *  fieldList = {
 *    "EmailAddress" = "{{Contact.Field(C_EmailAddress)}}"
 *   },
 *  requiresConfiguration = FALSE
 * )
 */
class DebugResponder extends EloquaAppCloudActionResponderBase implements EloquaAppCloudActionResponderInterface {

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientFactory $eloqua, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $eloqua);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eloqua.client_factory'),
      $container->get('logger.channel.eloqua_app_cloud')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($instanceId, $record, $query = NULL) {
    $this->logger->debug('Received action service hook with payload @record', [
      '@record' => print_r($record, TRUE),
    ]);
    return $record;
  }
}
