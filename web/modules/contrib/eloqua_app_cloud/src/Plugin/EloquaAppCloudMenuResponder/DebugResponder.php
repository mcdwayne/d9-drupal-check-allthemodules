<?php

namespace Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudMenuResponder;

use Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudMenuResponderBase;
use Drupal\eloqua_rest_api\Factory\ClientFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EloquaAppCloudMenuResponder(
 *  id = "MenuDebugResponder",
 *  label = @Translation("Debug Responder"),
 * )
 */
class DebugResponder extends EloquaAppCloudMenuResponderBase {

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
  public function execute(array &$render, array $params) {
    $this->logger->debug('Received menu service hook with params @params', [
      '@params' => print_r($params, TRUE),
    ]);
    /*$client = $this->eloqua->get();
    $email = $client->api('email')->show($params['assetId']);
    $render['LanguageForm'] = [
      '#type' => 'fieldset',
      '#title' => 'Language Options',
      '#collapsible' => FALSE,
    ];
    $render['LanguageForm']['languages'] = [
      '#type' => 'select',
      '#title' => 'Target Languages',
      '#description' => 'WTF BBQ',
      '#multiple' => TRUE,
      '#size' => 2,
      '#options' => [
        'en-US' => 'English (US)',
        'fr-FR' => 'French (FR)',
      ],
    ];
    $render['LanguageForm']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Push to WorldServer',
    ];
    $render['RedirectResponder'] = [
      '#type' => 'markup',
      '#markup' => '<h2>Asset Preview:</h2>' . $email['htmlContent']['html'],
    ];*/
    // Attach redirect HTTP headers to the response render array.
    //$render['#attached']['http_header'][] = ['Status', '302'];
    //$render['#attached']['http_header'][] = ['Location', 'https://www.eric.pe/terson/'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'MenuDebugResponder';
  }
}
