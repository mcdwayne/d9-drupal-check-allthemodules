<?php

namespace Drupal\domain_route_meta_tags\Helper;

/**
 * Class DomainRouteMetaHelper.
 *
 * @package Drupal\modules\domain_route_meta_tags
 */
class DomainRouteMetaHelper {

  /**
   * Keep class object.
   *
   * @var object
   */
  public static $helper = NULL;

  /**
   * Protected entityManager variable.
   *
   * @var entityManager
   */
  protected $entityManager;

  /**
   * Protected currentPath variable.
   *
   * @var currentPath
   */
  protected $currentPath;

  /**
   * Protected pathAlias variable.
   *
   * @var pathAlias
   */
  protected $pathAlias;

  /**
   * Protected negotiator variable.
   *
   * @var negotiator
   */
  protected $negotiator;

  /**
   * Private constructor to avoid instantiation.
   */
  private function __construct() {
  }

  /**
   * {@inheritdoc}
   *
   * Init call.
   */
  public function init() {
    $container = \Drupal::getContainer();
    $this->entityManager = $this->getMetaStorage($container);
    $this->currentPath = $this->getCurrentPath($container);
    $this->pathAlias = $this->getPathAlias($container);
    $this->negotiator = $this->getDomainNegotiator($container);
  }

  /**
   * Get class instance using this function.
   *
   * @return DomainRouteMetaHelper
   *   return Object.
   */
  public static function getInstance() {
    if (!self::$helper) {
      self::$helper = new DomainRouteMetaHelper();
      self::$helper->init();
    }
    return self::$helper;
  }

  /**
   * Get Meta Storage.
   */
  private function getMetaStorage($container = NULL) {
    return $container->get('entity.manager')->getStorage('domain_route_meta_tags');
  }

  /**
   * Get Current Path.
   */
  private function getCurrentPath($container = NULL) {
    return $container->get('path.current')->getPath();
  }

  /**
   * Get Path Alias.
   */
  private function getPathAlias($container = NULL) {
    return $container->get('path.alias_manager')->getAliasByPath($this->currentPath);
  }

  /**
   * Get Domain Negotiator.
   */
  private function getDomainNegotiator($container = NULL) {
    return $container->get('domain.negotiator');
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaEntity() {
    $domain = NULL;
    $entity = NULL;
    $entityData = NULL;
    if ($this->negotiator->getActiveDomain() !== NULL) {
      // Get Active Domain.
      $domain = $this->negotiator->getActiveDomain()->id();
    }
    $values = [
      'route_link' => $this->pathAlias,
      'domain' => $domain,
    ];
    // Load entity by property.
    $entityData = $this->entityManager->loadByProperties($values);
    if (empty($entityData)) {
      $values['route_link'] = $this->currentPath;
      $entityData = $this->entityManager->loadByProperties($values);
    }
    // Check for entity data.
    if ($entityData) {
      foreach ($entityData as $entity) {
        break;
      }
    }
    return $entity;
  }

}
