<?php

namespace Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudDecisionResponder;

use Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudDecisionResponderBase;
use Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudDecisionResponderInterface;
use Drupal\eloqua_rest_api\Factory\ClientFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EloquaAppCloudDecisionResponder(
 *  id = "DecisionDebugResponderNo",
 *  label = @Translation("Decision Debug Responder (no)"),
 *  description = "Simple decision debugging tool that always returns a NO for every record",
 *  api = "contacts",
 *  respond = "asynchronous",
 *  fieldList = {
 *    "EmailAddress" = "{{Contact.Field(C_EmailAddress)}}"
 *   },
 *  requiresConfiguration = FALSE
 * )
 */
class DebugResponderNo extends EloquaAppCloudDecisionResponderBase implements EloquaAppCloudDecisionResponderInterface {

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
    $this->logger->debug('Plugin says - received decision service hook with payload @record. Our decision is NO', [
      '@record' => print_r($record, TRUE),
    ]);
    $record->result = FALSE;
    return $record;
  }

}
