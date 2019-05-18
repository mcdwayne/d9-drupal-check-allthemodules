<?php

namespace Drupal\bibcite_export\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Export multiple references.
 *
 * @Action(
 *   id = "bibcite_export_multiple",
 *   label = @Translation("Export reference"),
 *   type = "bibcite_reference",
 *   confirm_form_route_name = "entity.bibcite_reference.export_multiple_form",
 * )
 */
class ExportReference extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ExportReference object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->tempStore = $temp_store_factory->get('bibcite_export_multiple');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tempstore.private'),
      $container->get('current_user')
    );
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
  public function executeMultiple(array $entities) {
    $info = [];
    /** @var \Drupal\bibcite_entity\Entity\ReferenceInterface $entity */
    foreach ($entities as $entity) {
      $info[$entity->id()] = $entity->label();
    }
    $this->tempStore->set($this->currentUser->id(), $info);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $account->hasPermission('access bibcite export') && $object->access('view', $account, $return_as_object);
  }

}
