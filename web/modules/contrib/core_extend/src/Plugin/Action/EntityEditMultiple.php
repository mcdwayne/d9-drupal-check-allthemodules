<?php

namespace Drupal\core_extend\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit multiple entities.
 *
 * @Action(
 *   id = "entity_edit_multiple",
 *   label = @Translation("Edit multiple")
 * )
 */
class EntityEditMultiple extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The tempstore object.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * Gets the tempstore collection id.
   *
   * @return string
   *   The collection ID.
   */
  protected function getTempStoreCollectionId() {
    return 'entity_edit_multiple';
  }

  /**
   * Constructs a new EntityEditMultipleBase action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Set defaults.
    $this->configuration += [
      'confirm_form_route_name' => '',
      'type' => '',
    ];

    // Override plugin definition with configuration.
    $this->pluginDefinition['confirm_form_route_name'] = $this->configuration['confirm_form_route_name'];
    $this->pluginDefinition['type'] = $this->configuration['type'];

    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->tempStore = $temp_store_factory->get($this->getTempStoreCollectionId());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('user.private_tempstore'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->pluginDefinition += [
      'type' => $this->configuration['type'],
      'confirm_form_route_name' => $this->configuration['confirm_form_route_name'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    // Filter actual entities.
    $entities = array_filter($entities, function ($entity) {
      return ($entity instanceof EntityInterface);
    });

    // Exit early if no entities are set to be edited.
    if (empty($entities)) {
      return NULL;
    }

    $data = [
      'entity_ids' => array_map(function (EntityInterface $entity) {
        return $entity->id();
      }, $entities),
      'entity_type_id' => current($entities)->getEntityTypeId(),
    ];

    $this->tempStore->set($this->currentUser->id(), $data);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Entity\EntityInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

}
