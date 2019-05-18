<?php

namespace Drupal\reference_map_adva\Plugin\adva\AccessProvider;

use Drupal\adva\Plugin\adva\AccessConsumerInterface;
use Drupal\adva\Plugin\adva\AccessProvider;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\reference_map\Plugin\ReferenceMapTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides access to entities via a reference map.
 *
 * @AccessProvider(
 *   id = "reference_map",
 *   label = @Translation("Reference Map Access"),
 *   operations = {
 *     "view",
 *     "update",
 *     "delete",
 *   },
 * )
 */
class ReferenceMapAccessProvider extends AccessProvider {

  use DependencySerializationTrait;

  /**
   * The Reference Map Type Manager.
   *
   * @var \Drupal\reference_map\Plugin\ReferenceMapTypeManagerInterface
   */
  private $referenceMapTypeManager;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Create a new ReferenceMapAccessProvider.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Unique plugin id.
   * @param array|mixed $plugin_definition
   *   Plugin instance definition.
   * @param \Drupal\adva\Plugin\adva\AccessConsumerInterface $consumer
   *   Associated Access Consumer Instance.
   * @param \Drupal\reference_map\Plugin\ReferenceMapTypeManagerInterface $reference_map_type_manager
   *   The Reference Map Type Plugin Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessConsumerInterface $consumer, ReferenceMapTypeManagerInterface $reference_map_type_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $consumer);

    $this->referenceMapTypeManager = $reference_map_type_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, AccessConsumerInterface $consumer) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $consumer,
      $container->get('plugin.manager.reference_map_type'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRecords(EntityInterface $entity) {
    $records = [];

    // Get all the advanced access maps that can have this entity as their
    // source.
    $maps = $this->entityTypeManager
      ->getStorage('reference_map_config')
      ->getQuery()
      ->condition('type', 'advanced_access')
      ->condition('sourceType', $entity->getEntityTypeId())
      ->execute();

    foreach ($maps as $map) {
      $map_plugin = $this->referenceMapTypeManager->getInstance([
        'plugin_id' => 'advanced_access',
        'map_id' => $map,
      ]);

      // There isn't a good way in an entity query to determine if an array
      // property has an element, so we need to check the bundle here.
      $source_bundles = $map_plugin->getConfig()->sourceBundles;
      if (empty($source_bundles) || in_array($entity->bundle(), $source_bundles)) {
        $records = array_merge($records, $map_plugin->getRecords($entity));
      }
    }

    return $records;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessGrants($operation, AccountInterface $account) {
    $account_id = $account->id();
    $grants = [];

    // Get all access reference maps.
    $maps = $this
      ->entityTypeManager
      ->getStorage('reference_map_config')
      ->getQuery()
      ->condition('type', 'advanced_access')
      ->condition('sourceType', $this->getConsumer()->getEntityTypeId())
      ->execute();

    foreach ($maps as $map) {
      $grants['reference_map_adva_' . $map] = [$account_id];
    }

    return $grants;
  }

  /**
   * {@inheritdoc}
   */
  public static function getHelperMessage(array $definition) {
    $context = [
      '%provider' => $definition['label'],
    ];
    $message = \Drupal::translation()->translate('<em>%provider</em> is supported on any entity type.', $context);
    $message .= ' ' . \Drupal::translation()->translate('<em>%provider</em> allows access based on Reference Maps.', $context);

    return $message;
  }

}
