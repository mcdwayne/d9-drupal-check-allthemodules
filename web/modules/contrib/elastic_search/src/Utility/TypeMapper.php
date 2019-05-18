<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 13/12/16
 * Time: 17:53
 */

namespace Drupal\elastic_search\Utility;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\elastic_search\Event\FieldMapperSupports;
use Drupal\elastic_search\Exception\TypeMapperException;
use Drupal\elastic_search\Plugin\FieldMapperInterface;
use Drupal\elastic_search\Plugin\FieldMapperManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class TypeMapper
 *
 * @package Drupal\elastic_search\Utility
 */
class TypeMapper implements ContainerInjectionInterface {

  use UseCacheBackendTrait;

  /**
   * Name of logging channel to use
   */
  const LOGGER_NAME = 'elastic_search.TypeMapper';

  /**
   * Id of the cache to use for the server mappings
   */
  const CACHE_ID = 'elastic_search.server.mapping';

  /**
   * @var \Drupal\elastic_search\Plugin\FieldMapperManager
   */
  protected $fieldMapperManager;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * TypeMapper constructor.
   *
   * @param \Drupal\elastic_search\Plugin\FieldMapperManager            $fieldMapperManager
   * @param \Drupal\Core\Cache\CacheBackendInterface                    $cacheBackend
   * @param \Psr\Log\LoggerInterface                                    $logger
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   */
  public function __construct(FieldMapperManager $fieldMapperManager,
                              CacheBackendInterface $cacheBackend,
                              LoggerInterface $logger,
                              EventDispatcherInterface $dispatcher) {
    $this->fieldMapperManager = $fieldMapperManager;
    $this->cacheBackend = $cacheBackend;//provided by UseCacheBackendTrait
    $this->logger = $logger;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.elastic_field_mapper_plugin'),
                      $container->get('cache.default'),
                      $container->get('logger.factory')->get(self::LOGGER_NAME),
                      $container->get('event_dispatcher')
    );
  }

  /**
   * @param string $fieldId
   *
   * @return array
   *
   * @throws \Exception
   */
  public function getFieldOptions(string $fieldId): array {

    if (!$this->hasCache()) {
      $this->buildCache();
    }
    return $this->getFieldOptionValue($fieldId);
  }

  /**
   * @param string $type
   * @param array  $defaults
   * @param int    $depth
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getFormAdditions(string $type,
                                   array $defaults = [],
                                   int $depth = 0): array {
    /** @var FieldMapperInterface $field */
    $field = $this->fieldMapperManager->createInstance($type);
    return $field->getFormFields($defaults, $depth);
  }

  /**
   * @param string $type
   *
   * @return bool
   *
   * @throws \Exception
   */
  public function supportsFields(string $type): bool {

    //TODO - results should be cached
    $supportsFields = FALSE;

    if (!$this->hasCache()) {
      $this->buildCache();
    }
    $cache = $this->cacheGet(self::CACHE_ID)->data['supports'];

    if (array_key_exists($type, $cache)) {
      $fieldTypes = $cache[$type];
      foreach ($fieldTypes as $fieldType) {
        /** @var FieldMapperInterface $def */
        $def = $this->fieldMapperManager->createInstance($fieldType);
        $supportsFields |= $def->supportsFields();
      }
    }
    return (bool) $supportsFields;
  }

  /**
   * @return bool
   */
  private function hasCache(): bool {
    $cache = $this->cacheGet(self::CACHE_ID);
    return ((bool) $cache && $cache->valid);
  }

  /**
   * @param string $fieldId
   *
   * @return array
   */
  private function getFieldOptionValue(string $fieldId): array {

    $cache = $this->cacheGet(self::CACHE_ID)->data['supports'];
    if (array_key_exists($fieldId, $cache)) {
      //We have an entry already in the cache
      return $cache[$fieldId];
    } else {
      //If the key does not have any explicit mappings then just return 'all' or nothing
      return array_key_exists('all', $cache) ? $cache['all'] : [];
    }
  }

  /**
   * @return false|object
   * @throws \Exception
   */
  private function buildCache() {

    $cache = [];

    $cache['supports'] = $this->buildTypeMappingsCache();

    //TODO - cache tags on new plugins?
    $this->cacheSet(self::CACHE_ID, $cache);
    if (!$this->cacheGet(self::CACHE_ID)) {
      //This should never happen if the cache backend is set up properly,
      // since the trait function doesnt throw anything we need to be aware of it
      throw new TypeMapperException('Could not create cache');
    }
    return $this->cacheGet(self::CACHE_ID);
  }

  /**
   * @return array
   *
   * @throws \Exception
   */
  private function buildTypeMappingsCache(): array {

    //Get all the elastic field mapper types
    $definitions = $this->fieldMapperManager->getDefinitions();

    $supports = array_map(function ($definition) {
      try {

        $supportedTypes = $this->fieldMapperManager->createInstance($definition['id'])
                                                   ->getSupportedTypes();

        $e = new FieldMapperSupports($definition['id'],
                                     $supportedTypes);
        /** @var FieldMapperSupports $event */
        $event = $this->dispatcher->dispatch('elastic_search.field_mapper.supports.' .
                                             $definition['id'],
                                             $e);
        return $event->getSupported();
      } catch (\Exception $e) {
        $this->logger->warning('Exception when getting plugin of type: ' .
                               $definition['id'] . ' : ' . $e->getMessage());
        return [];
      }
    },
      $definitions);

    $reordered = $this->orderByDrupalFieldType($supports);

    //merge in the 'all' options to each drupal field
    if (array_key_exists('all', $reordered)) {
      $all = $reordered['all'];
      $reordered = array_map(function ($v) use ($all) {
        return array_merge($all, $v);
      },
        $reordered);
    }

    ksort($reordered);

    return $reordered;
  }

  /**
   * @param array $data
   *
   * @return array
   */
  private function orderByDrupalFieldType(array $data): array {
    //Reorder the results to be indexed by the drupal field
    $reordered = [];
    foreach ($data as $elastic_type => $drupal_types) {
      foreach ($drupal_types as $drupal_type) {
        $reordered[$drupal_type][$elastic_type] = $elastic_type;
      }
    }
    return $reordered;
  }

}