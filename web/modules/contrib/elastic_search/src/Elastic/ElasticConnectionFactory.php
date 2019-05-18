<?php

namespace Drupal\elastic_search\Elastic;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticConnectionFactory
 *
 * @package Drupal\elastic_search\Elastic
 */
class ElasticConnectionFactory implements ContainerInjectionInterface {

  use StringTranslationTrait;
  /**
   * Default logging channel identifier
   */
  const DEFAULT_LOGGING_CHANNEL = 'elastic_search.connection';

  /**
   * @var Client
   */
  private $connection;

  /**
   * @var ImmutableConfig
   */
  private $storedConfig;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * ElasticConnectionFactory constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig                 $storedConfig
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface   $loggerChannelFactory
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   */
  public function __construct(ImmutableConfig $storedConfig,
                              LoggerChannelFactoryInterface $loggerChannelFactory,
                              TranslationInterface $translation) {
    $this->storedConfig = $storedConfig;
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->stringTranslation = $translation;
  }

  /**
   * @inheritDoc
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory')
                                ->get('elastic_search.server'),
                      $container->get('logger.factory'),
                      $container->get('string_translation'));
  }

  /**
   * @param array $configuration
   *
   * @return \Elasticsearch\Client|null
   *
   * @throws \Exception
   */
  public function getElasticConnection(array $configuration = NULL) {

    if ($this->connection) {
      return $this->connection;
    }

    if ($configuration === NULL) {
      ///If no config is passed in then get the one from the search api backend
      $configuration = $this->storedConfig->getRawData();
    }

    $elastic = ClientBuilder::create();

    try {

      $elastic->setHosts($this->buildHost($configuration));
    } catch (\Throwable $t) {
      if ($configuration['advanced']['validate']['active'] ?? FALSE) {
        throw $t;
      }

    }

    if ($configuration['advanced']['developer']['active'] ?? FALSE) {
      $elastic->setLogger($this->loggerChannelFactory->get($configuration['advanced']['developer']['logging_channel']
                                                           ??
                                                           self::DEFAULT_LOGGING_CHANNEL));
    }

    $elastic_connection = $elastic->build();

    //Validate that we can reach the server. If not it will throw an Exception
    if ($configuration['advanced']['validate']['active'] ?? FALSE) {
      try {
        //This is not a future as we want to resolve it asap and check our connection works
        $elastic_connection->info(['client' => ['timeout' => 5]]);
      } catch (\Exception $e) {
        $this->loggerChannelFactory->get('elastic.connection_factory')
                                   ->critical($this->t('Failed to connect to Elasticsearch'));
        $elastic_connection = NULL;
        if ($configuration['advanced']['validate']['die_hard'] ?? FALSE) {
          $this->connection = $elastic_connection;
          throw $e;
        }
      }
    }

    $this->connection = $elastic_connection;
    return $this->connection;
  }

  /**
   * @param array $configuration
   *
   * @return array
   *
   * @throws \InvalidArgumentException
   */
  protected function buildHost(array $configuration) {
    //TODO - revisit this once elastic client works properly with arrays
    //For now due to some of the filters they use the host needs to be built into a specific string
    foreach (['scheme', 'host', 'port'] as $item) {
      $this->testMissingKey($item, $configuration);
    }

    $host = $configuration['scheme'] . '://';
    if (!empty($configuration['auth']['username']) &&
        !empty($configuration['auth']['password'])
    ) {
      $host .= $configuration['auth']['username'] . ':' .
               $configuration['auth']['password'] . '@';
    }
    $host .= $configuration['host'] . ':' . $configuration['port'];

    return [$host];
  }

  /**
   * @param string $key
   * @param array  $configuration
   *
   * @throws \InvalidArgumentException
   */
  protected function testMissingKey(string $key, array $configuration) {
    if (!array_key_exists($key, $configuration)) {
      throw new \InvalidArgumentException($key .
                                          ' key not found in configuration');
    }
  }

}