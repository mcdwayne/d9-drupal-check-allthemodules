<?php

namespace Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudContentResponder;

use Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudContentResponderBase;
use Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudContentResponderInterface;
use Drupal\eloqua_rest_api\Factory\ClientFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EloquaAppCloudContentResponder(
 *  id = "ContentDebugResponderAsync",
 *  label = @Translation("Asynchronous Content Debug Responder"),
 *  description = "Simple asynchronous content debugging tool that always returns the same HTML for every record",
 *  respond = "asynchronous",
 *  api = "contacts",
 *  fieldList = {
 *    "EmailAddress" = "{{Contact.Field(C_EmailAddress)}}"
 *   },
 *  requiresConfiguration = FALSE,
 *  height = "",
 *  width = "",
 *  editorImageUrl = ""
 * )
 */
class DebugResponderAsync extends EloquaAppCloudContentResponderBase implements EloquaAppCloudContentResponderInterface {

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
    $this->logger->debug('Received content service hook with payload @record', [
      '@record' => print_r($record, TRUE),
    ]);
    $record->content = "<div>DEBUG CONTENT</div>";
    return $record;
  }
}
