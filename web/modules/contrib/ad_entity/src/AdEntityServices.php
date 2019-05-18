<?php

namespace Drupal\ad_entity;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\ad_entity\Plugin\AdViewManager;
use Drupal\ad_entity\Plugin\AdTypeManager;
use Drupal\ad_entity\Plugin\AdContextManager;

/**
 * Offers the services which are usually used by Advertising entities.
 */
class AdEntityServices implements EntityHandlerInterface {

  /**
   * The Advertising view manager.
   *
   * @var \Drupal\ad_entity\Plugin\AdViewManager
   */
  protected $viewManager;

  /**
   * The Advertising type manager.
   *
   * @var \Drupal\ad_entity\Plugin\AdTypeManager
   */
  protected $typeManager;

  /**
   * The Advertising context manager.
   *
   * @var \Drupal\ad_entity\Plugin\AdContextManager
   */
  protected $contextManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return $container->get('ad_entity');
  }

  /**
   * Get the Advertising view manager.
   *
   * @return \Drupal\ad_entity\Plugin\AdViewManager
   *   The view manager.
   */
  public function getViewManager() {
    return $this->viewManager;
  }

  /**
   * Set the Advertising view manager.
   *
   * @param \Drupal\ad_entity\Plugin\AdViewManager $view_manager
   *   The view manager.
   *
   * @return \Drupal\ad_entity\AdEntityServices
   *   The services handler itself.
   */
  public function setViewManager(AdViewManager $view_manager) {
    $this->viewManager = $view_manager;
    return $this;
  }

  /**
   * Get the Advertising type manager.
   *
   * @return \Drupal\ad_entity\Plugin\AdTypeManager
   *   The type manager.
   */
  public function getTypeManager() {
    return $this->typeManager;
  }

  /**
   * Set the Advertising type manager.
   *
   * @param \Drupal\ad_entity\Plugin\AdTypeManager $type_manager
   *   The type manager.
   *
   * @return \Drupal\ad_entity\AdEntityServices
   *   The services handler itself.
   */
  public function setTypeManager(AdTypeManager $type_manager) {
    $this->typeManager = $type_manager;
    return $this;
  }

  /**
   * Get the Advertising context manager.
   *
   * @return \Drupal\ad_entity\Plugin\AdContextManager
   *   The context manager.
   */
  public function getContextManager() {
    return $this->contextManager;
  }

  /**
   * Set the Advertising context manager.
   *
   * @param \Drupal\ad_entity\Plugin\AdContextManager $context_manager
   *   The context manager.
   *
   * @return \Drupal\ad_entity\AdEntityServices
   *   The services handler itself.
   */
  public function setContextManager(AdContextManager $context_manager) {
    $this->contextManager = $context_manager;
    return $this;
  }

  /**
   * Clears all cached plugin definitions from the services.
   */
  public function clearCachedDefinitions() {
    $this->viewManager->clearCachedDefinitions();
    $this->typeManager->clearCachedDefinitions();
    $this->contextManager->clearCachedDefinitions();
  }

}
