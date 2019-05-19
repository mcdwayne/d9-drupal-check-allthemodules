<?php

namespace Drupal\snipcart\Plugin\rest\resource;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;


/**
 * Provides a resource to Snipcart data-uri result
 *
 * @RestResource(
 *   id = "snipcart_product",
 *   label = @Translation("Commerce Product for Snipcart"),
 *   uri_paths = {
 *     "canonical" = "/snipcart/{product_id}"
 *   }
 * )
 */
class SnipcartProductResource extends ResourceBase {

  /**
   *  A instance of entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   An Entity type manager
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * Retrieve a Snipcart data-uri export
   *
   * @param integer $product_id
   *   the product variation id
   * @return \Drupal\rest\ResourceResponse
   *   the service response
   */
  public function get($product_id = NULL) {

    /** @var ProductVariationInterface $product_variation */
    $product_variation = $this->entity_type_manager->getStorage('commerce_product_variation')->load($product_id);

    $data = [
      'id' => $product_id,
      'name' => $product_variation->label(),
      'sku' => $product_variation->getSku(),
      'price' => [strtolower($product_variation->getPrice()->getCurrencyCode()) => floatval($product_variation->getPrice()->getNumber())],
    ];
    $response =  new ResourceResponse($data);

    $response->addCacheableDependency($product_variation);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager')
    );
  }

}
