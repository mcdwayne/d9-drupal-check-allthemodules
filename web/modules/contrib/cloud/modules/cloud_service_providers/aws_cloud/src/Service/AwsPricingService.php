<?php

namespace Drupal\aws_cloud\Service;

use Drupal\cloud\Entity\CloudConfig;
use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use GuzzleHttp\Client;

/**
 * Service AwsPricingService.
 */
class AwsPricingService implements AwsPricingServiceInterface {

  use StringTranslationTrait;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * CloudConfigPlugin.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface
   */
  protected $cloudConfigPluginManager;

  /**
   * Cloud context string.
   *
   * @var string
   */
  private $cloudContext;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Guzzle http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * TRUE to run the operation, FALSE to run the operation in validation mode.
   *
   * @var bool
   */
  private $dryRun;

  /**
   * The base url for the pricing endpoint.
   *
   * @var string
   */
  private $ec2PricingEndpoint = 'https://pricing.us-east-1.amazonaws.com/offers/v1.0/aws/AmazonEC2/current';

  /**
   * The cloud configuration entity.
   *
   * @var \Drupal\cloud\Entity\CloudConfig
   */
  private $cloudConfigEntity = FALSE;

  /**
   * Constructs a new AwsEc2Service object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The cloud config plugin manager.
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle Http client.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    Messenger $messenger,
    TranslationInterface $string_translation,
    CloudConfigPluginManagerInterface $cloud_config_plugin_manager,
    Client $http_client
  ) {
    $this->logger = $logger_factory->get('aws_pricing_service');

    // Setup the configuration factory.  Not really needed at this point.
    // Could be useful at a later date.
    $this->configFactory = $config_factory;

    // Setup the dryRun flag.
    $this->dryRun = (bool) $this->configFactory->get('aws_cloud.settings')->get('aws_cloud_test_mode');
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function setCloudConfigEntity(CloudConfig $cloud_config_entity) {
    $this->cloudConfigEntity = $cloud_config_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setCloudContext($cloud_context) {
    $this->cloudContext = $cloud_context;
  }

  /**
   * Generate the full pricing endpoint.
   *
   * @return bool|string
   *   The full pricing endpoint.
   */
  private function getPricingEndpoint() {
    $endpoint = FALSE;
    if ($this->cloudConfigEntity != FALSE) {
      $endpoint = $this->ec2PricingEndpoint . "/{$this->cloudConfigEntity->get('field_region')->value}/index.json";
    }
    return $endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceTypes() {
    $mock_data = $this->configFactory
      ->get('aws_cloud.settings')
      ->get('aws_cloud_mock_instance_types');
    if ($this->dryRun && $mock_data) {
      return json_decode($mock_data, TRUE);
    }

    // Give max memory for json decoding.
    ini_set('memory_limit', '-1');
    $instance_types = [];
    try {
      $url = $this->getPricingEndpoint();
      if ($url != FALSE) {
        $response = $this->httpClient->get($url, [
          'timeout' => 0,
        ]);
        $data = (string) $response->getBody();
        if (!empty($data)) {
          $instance_products = [];
          $pricing = \GuzzleHttp\json_decode($data);
          foreach ($pricing->products as $product) {
            if ($product->productFamily == 'Compute Instance'
              && $product->attributes->operatingSystem == 'Linux'
              && $product->attributes->tenancy != 'Dedicated'
              && $product->attributes->preInstalledSw == 'NA'
              && isset($product->attributes->instancesku)
            ) {
              $instance_products[$product->attributes->instancesku] = $product->attributes;
            }
          }

          // Add on-demand price.
          foreach ($pricing->terms->OnDemand as $term) {
            $items = array_values((array) $term);

            foreach ($items as $item) {
              $prices = array_values((array) $item->priceDimensions);

              $found = FALSE;
              foreach ($prices as $price) {
                if ($price
                  && $price->unit == 'Hrs'
                  && $price->pricePerUnit->USD
                  && $this->isOnDemandInstance($price)
                ) {
                  if (isset($instance_products[$item->sku])) {
                    $instance_products[$item->sku]->price = floatval($price->pricePerUnit->USD);
                    $found = TRUE;
                    break;
                  }
                }
              }

              if ($found) {
                break;
              }
            }
          }

          // Add reserved instance price.
          foreach ($pricing->terms->Reserved as $term) {

            $items = array_values((array) $term);
            foreach ($items as $item) {
              if (!isset($item->termAttributes->LeaseContractLength)) {
                continue;
              }

              if ($item->termAttributes->LeaseContractLength != '1yr'
                && $item->termAttributes->LeaseContractLength != '3yr'
              ) {
                continue;
              }

              if ($item->termAttributes->OfferingClass != 'standard'
                || $item->termAttributes->PurchaseOption != 'All Upfront'
              ) {
                continue;
              }

              $prices = array_values((array) $item->priceDimensions);

              $found = FALSE;
              foreach ($prices as $price) {
                if ($price
                  && $price->unit == 'Quantity'
                  && $price->pricePerUnit->USD
                ) {
                  if (isset($instance_products[$item->sku])) {
                    if ($item->termAttributes->LeaseContractLength == '1yr') {
                      $property_name = 'one_year_price';
                    }
                    else {
                      $property_name = 'three_year_price';
                    }
                    $instance_products[$item->sku]->$property_name = floatval($price->pricePerUnit->USD);

                    break;
                  }
                }
              }
            }
          }

          uasort($instance_products, function ($a, $b) {
            $a_type = explode('.', $a->instanceType)[0];
            $b_type = explode('.', $b->instanceType)[0];
            if ($a_type < $b_type) {
              return -1;
            }
            elseif ($a_type > $b_type) {
              return 1;
            }

            return floatval($a->price) < floatval($b->price) ? -1 : 1;
          });

          foreach ($instance_products as $product) {
            $instance_types[$product->instanceType] = sprintf(
              '%s:%s:%s:%s:%s:%s:%s',
              $product->instanceType,
              $product->vcpu,
              $product->ecu,
              $product->memory,
              isset($product->price) ? $product->price : '',
              isset($product->one_year_price) ? $product->one_year_price : '',
              isset($product->three_year_price) ? $product->three_year_price : ''
            );
          }
        }
      }
      else {
        $this->messenger
          ->addError(t('Unable to set pricing endpoint for @cloud_context.  Cannot retrieve instance types.', [
            '@cloud_context' => $this->cloudContext,
          ]));
      }
    }
    catch (RequestException $e) {
      $this->messenger->addError(t('Error retrieving instance types.'));
      // Empty out the array so it does not get cached with empty values.
      $instance_types = [];
    }

    return $instance_types;
  }

  /**
   * Helper function to check whether the instance is on demand.
   *
   * @param object $price
   *   The price object.
   *
   * @return bool
   *   The result of check.
   */
  private function isOnDemandInstance($price) {
    $forbidden_words = ['Windows', 'Reservation', 'Dedicated'];
    foreach ($forbidden_words as $word) {
      if (strpos($price->description, $word) !== FALSE) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
