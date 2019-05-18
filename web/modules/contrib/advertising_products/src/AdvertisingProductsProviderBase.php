<?php

namespace Drupal\advertising_products;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base implementation of advertising products provider plugin.
 */
abstract class AdvertisingProductsProviderBase extends PluginBase implements AdvertisingProductsProviderInterface {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entityManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIdFromProductId($product_id) {
    $query = \Drupal::entityQuery('advertising_product')
      ->condition('product_id', $product_id);
    $entity_ids = $query->execute();
    if ($entity_ids) {
      return reset($entity_ids);
    }
    return NULL;
  }

  /**
   * @inheritdoc
   */
  public function getImagePrefix($product_data) {
    $parts = [
      'product',
      uniqid(),
    ];
    return implode('-', $parts);
  }

  /**
   * @inheritdoc
   */
  public function saveImage(ResponseInterface $response, $product_data) {
    /** @var \Drupal\file\FileInterface $file */
    $suffix = '.jpg';
    if ($response->getHeader('content-type')[0] == 'image/png')  {
      $suffix = '.png';
    }

    $prefix = $this->getImagePrefix($product_data);

    $file = file_save_data($response->getBody(), 'public://' . $prefix . $suffix, FILE_EXISTS_REPLACE);
    image_path_flush($file->getFileUri());

    return $file;
  }

  /**
   * @inheritdoc
   */
  public function submitFieldWidget(array $values) {
    return true;
  }

}
