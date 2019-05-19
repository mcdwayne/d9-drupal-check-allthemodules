<?php

namespace Drupal\views_tag_access;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views_ui\ViewListBuilder as OriginalViewListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of view entities.
 *
 * We need to override \Drupal\views_ui\ViewListBuilder to apply our additional
 * access checks before showing views to users.
 *
 * @see \Drupal\views\Entity\View
 */
class ViewListBuilder extends OriginalViewListBuilder {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The private tempstore.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempstoreFactory;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.views.display'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * Constructs a new ViewListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage.
   *   The entity storage class.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $display_manager
   *   The views display plugin manager to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\user\PrivateTempStoreFactory $tempstore_factory
   *   The private tempstore.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, PluginManagerInterface $display_manager, ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, PrivateTempStoreFactory $tempstore_factory) {
    parent::__construct($entity_type, $storage, $display_manager);
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->tempstoreFactory = $tempstore_factory;
  }

  /**
   * {@inheritdoc}
   *
   * We override this to filter our views that we don't have access for.
   */
  public function load() {
    $entities = parent::load();

    // If we don't have administer views, we need to check each one.
    if (!$this->currentUser->hasPermission('administer views')) {
      // Check for any that we have no access to.
      foreach ($entities as &$group) {
        foreach ($group as $key => $entity) {
          $tag_helper = new ViewsTagAccessHelper($entity, $this->configFactory, $this->currentUser, $this->tempstoreFactory);
          $access_result = $tag_helper->access(NULL, $this->currentUser);

          // If we don't have access, remove it from the list.
          if (!$access_result->isAllowed()) {
            unset($group[$key]);
          }
        }
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Remove when https://www.drupal.org/node/2650898 is fixed.
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Core doesn't provide access checks for enable/disable/duplicate.
    foreach (['enable', 'disable', 'duplicate'] as $op) {
      if (isset($operations[$op]) && !$entity->access($op)) {
        unset($operations[$op]);
      }
    }

    return $operations;
  }

}
