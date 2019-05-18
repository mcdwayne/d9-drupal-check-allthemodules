<?php

namespace Drupal\chatbot_api_entities\Plugin;

use Drupal\chatbot_api_entities\Entity\EntityCollection;
use Drupal\chatbot_api_entities\Entity\EntityCollectionInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Push handler plugins.
 */
abstract class PushHandlerBase extends PluginBase implements PushHandlerInterface, ContainerFactoryPluginInterface {

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new PushHandlerBase object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   HTTP client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $httpClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * Format entities for pushing.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   Array of entities to push.
   * @param \Drupal\chatbot_api_entities\Entity\EntityCollection $entityCollection
   *   Configuration object for the collection.
   *
   * @return array
   *   Array of entries each with keys 'value' and 'synonyms'
   */
  protected function formatEntries(array $entities, EntityCollection $entityCollection) {
    $formatted = [];
    foreach ($entities as $entity) {
      $formatted[] = [
        'value' => $entity->label(),
        'synonyms' => $entityCollection->getSynonyms($entity),
      ];
    }
    return $formatted;
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfiguration(EntityCollectionInterface $entityCollection, array $configuration) {
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return !empty($this->configuration['status']);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(EntityCollectionInterface $entityCollection, array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(EntityCollectionInterface $entityCollection, array $form, FormStateInterface $form_state) {}

}
