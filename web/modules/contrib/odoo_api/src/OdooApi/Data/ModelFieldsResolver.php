<?php

namespace Drupal\odoo_api\OdooApi\Data;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\odoo_api\OdooApi\ClientInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\odoo_api\OdooApi\Util\ResponseCacheTrait;

/**
 * Class ModelFieldsResolver.
 */
class ModelFieldsResolver implements ModelFieldsResolverInterface {

  use ResponseCacheTrait;

  /**
   * Drupal\odoo_api\OdooApi\ClientInterface definition.
   *
   * @var \Drupal\odoo_api\OdooApi\ClientInterface
   */
  protected $odooApiApiClient;

  /**
   * ModelFieldsResolver constructor.
   *
   * @param \Drupal\odoo_api\OdooApi\ClientInterface $odoo_api_api_client
   *   The Odoo API client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_service
   *   The cache service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(ClientInterface $odoo_api_api_client, CacheBackendInterface $cache_service, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->odooApiApiClient = $odoo_api_api_client;
    $this->setCacheOptions($cache_service, $cache_tags_invalidator, 'odoo_api.model_fields_resolver');
  }

  /**
   * {@inheritdoc}
   */
  public function getModelFieldsData($model_name) {
    return $this->cacheResponse('models_' . $model_name, function () use ($model_name) {
      return $this->odooApiApiClient->fieldsGet($model_name);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType($field_name, $model_name) {
    $fields = $this->getModelFieldsData($model_name);

    if (empty($fields[$field_name])) {
      return FALSE;
    }

    return $fields[$field_name]['type'];
  }

}
