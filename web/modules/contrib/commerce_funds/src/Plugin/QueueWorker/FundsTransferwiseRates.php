<?php

namespace Drupal\commerce_funds\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes interface translation queue tasks.
 *
 * @QueueWorker(
 *   id = "commerce_funds_transferwise_rates",
 *   title = @Translation("Update currency conversion rates"),
 *   cron = {"time" = 60}
 * )
 */
class FundsTransferwiseRates extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, ClientInterface $http_client, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClient = $http_client;
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
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('logger.channel.form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($token) {
    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();

    foreach ($currencies as $currency_left) {
      $currency_code_left = $currency_left->getCurrencyCode();
      foreach ($currencies as $currency_right) {
        $currency_code_right = $currency_right->getCurrencyCode();
        if ($currency_code_left !== $currency_code_right) {
          $rate = $this->getPairOfCurrencyRate($token, $currency_code_left, $currency_code_right);
          $rate = $rate ?: $this->config->get('commerce_funds.settings')->get('exchange_rates')[$currency_code_left . '_' . $currency_code_right];
          $exchange_rates[$currency_code_left . '_' . $currency_code_right] = $rate;
        }
      }
    }

    $this->config->getEditable('commerce_funds.settings')
      ->set('exchange_rates', $exchange_rates)
      ->save();
  }

  /**
   * Get Transferwise API rate for a pair of currency.
   *
   * @param string $token
   *   The transferwise sandbox API token.
   * @param string $currency_code_left
   *   The source currency.
   * @param string $currency_code_right
   *   The target currency.
   *
   * @return string
   *   The transferwise rate.
   */
  protected function getPairOfCurrencyRate($token, $currency_code_left, $currency_code_right) {
    try {
      $response = $this->httpClient->request('GET', 'https://api.sandbox.transferwise.tech/v1/rates', [
        'headers' => [
          'authorization' => 'Bearer ' . $token,
        ],
        'query' => [
          'source' => $currency_code_left,
          'target' => $currency_code_right,
        ],
      ]);
    }
    catch (Exception $e) {
      $this->logger->error($e->getMessage());
    }
    finally {
      if ($response->getStatusCode() == "200") {
        $contents = $response->getBody()->getContents();

        return (string) Json::decode($contents)[0]['rate'];
      }
      else {
        return '0';
      }
    }
  }

}
