<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\aws_cloud\Service\AwsEc2ServiceInterface;
use Drupal\cloud\Form\CloudContentDeleteForm;
use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;
use Drupal\cloud\Service\EntityLinkRendererInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AwsDeleteForm - Base Delete class.
 *
 * This class injects the AwsEc2ServiceInterface and Messenger for use.
 *
 * @package Drupal\aws_cloud\Form\Ec2
 */
class AwsDeleteForm extends CloudContentDeleteForm {

  /**
   * The AWS EC2 Service.
   *
   * @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface
   */
  protected $awsEc2Service;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * A plugin cache clear instance.
   *
   * @var \Drupal\Core\Plugin\CachedDiscoveryClearerInterface
   */
  protected $pluginCacheClearer;

  /**
   * A cache backend interface instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * Entity link renderer object.
   *
   * @var \Drupal\cloud\Service\EntityLinkRendererInterface
   */
  protected $entityLinkRenderer;

  /**
   * The cloud config plugin manager service.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface
   */
  protected $cloudConfigPluginManager;

  /**
   * AwsDeleteForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The Entity Manager.
   * @param \Drupal\aws_cloud\Service\AwsEc2ServiceInterface $aws_ec2_service
   *   The AWS EC2 Service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The Messenger Service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheRender
   *   A cache backend interface instance.
   * @param \Drupal\Core\Plugin\CachedDiscoveryClearerInterface $plugin_cache_clearer
   *   A plugin cache clear instance.
   * @param \Drupal\cloud\Service\EntityLinkRendererInterface $entity_link_renderer
   *   The entity link render service.
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The cloud config plugin manager service.
   */
  public function __construct(EntityManagerInterface $manager,
                              AwsEc2ServiceInterface $aws_ec2_service,
                              Messenger $messenger,
                              EntityRepositoryInterface $entity_repository,
                              EntityTypeManagerInterface $entity_type_manager,
                              CacheBackendInterface $cacheRender,
                              CachedDiscoveryClearerInterface $plugin_cache_clearer,
                              EntityLinkRendererInterface $entity_link_renderer,
                              CloudConfigPluginManagerInterface $cloud_config_plugin_manager) {
    parent::__construct($manager, $entity_repository, $messenger);
    $this->awsEc2Service = $aws_ec2_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityLinkRenderer = $entity_link_renderer;
    $this->cacheRender = $cacheRender;
    $this->pluginCacheClearer = $plugin_cache_clearer;
    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('aws_cloud.ec2'),
      $container->get('messenger'),
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('cache.render'),
      $container->get('plugin.cache_clearer'),
      $container->get('entity.link_renderer'),
      $container->get('plugin.manager.cloud_config_plugin')
    );
  }

  /**
   * Helper method to clear cache values.
   */
  protected function clearCacheValues() {
    $this->pluginCacheClearer->clearCachedDefinitions();
    $this->cacheRender->invalidateAll();
  }

}
