<?php

namespace Drupal\tmgmt_extension_suit\Plugin\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use \Drupal\user\PrivateTempStoreFactory;

abstract class BaseJobAction extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates TranslateNode action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PrivateTempStoreFactory $temp_store_factory,
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory;
  }


  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    //** @var \Drupal\node\NodeInterface $object */
    // @todo Move the check to own service.
    $result = content_translation_translate_access($object);

    //return $return_as_object ? $result : $result->isAllowed();
    return TRUE;
  }

  abstract protected function getTempStoreName($entity_type = '');

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('user.private_tempstore'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $ids = [];

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($entities as $entity) {
      $ids[$entity->id()] = $entity->getEntityTypeId();
    }

    $entity = reset($entities);
    if ($entity instanceof EntityInterface) {
      $entity_type = $entity->getEntityTypeId();
    }
    $this->tempStoreFactory->get($this->getTempStoreName($entity_type))
      ->set($this->currentUser->id(), $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ContentEntityInterface $entity = NULL) {
    $this->executeMultiple([$entity]);
  }

}
