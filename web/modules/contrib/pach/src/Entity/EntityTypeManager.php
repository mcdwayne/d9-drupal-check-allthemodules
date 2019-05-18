<?php

namespace Drupal\pach\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager as CoreEntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Overrides core EntityTypeManager to allow pluggable access control handlers.
 *
 * @see \Drupal\Core\Entity\EntityTypeManager
 */
class EntityTypeManager extends CoreEntityTypeManager implements EntityTypeManagerInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The decorated entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $coreEntityTypeManager;

  /**
   * Constructs a new Entity plugin manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The decorated entity type manager.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to use.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, \Traversable $namespaces, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache, TranslationInterface $string_translation, ClassResolverInterface $class_resolver) {
    $this->coreEntityTypeManager = $entity_type_manager;
    parent::__construct($namespaces, $module_handler, $cache, $string_translation, $class_resolver);
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler($entity_type, $handler_type) {
    /* @var $handler_orig \Drupal\Core\Entity\EntityTypeInterface */
    $handler_orig = parent::getHandler($entity_type, $handler_type);
    if ('access' !== $handler_type) {
      // Do not alter other handler types.
      return $handler_orig;
    }
    if ($handler_orig instanceof \Drupal\pach\Entity\PluggableAccessControlHandlerInterface) {
      // Handler is already altered.
      return $handler_orig;
    }

    // Replace original access control handler.
    /* @var $definition \Drupal\Core\Entity\EntityTypeInterface */
    $definition = $this->getDefinition($entity_type);
    $_class = $definition->getHandlerClass($handler_type);
    if (!$_class) {
      throw new InvalidPluginDefinitionException($entity_type, sprintf('The "%s" entity type did not specify a %s handler.', $entity_type, $handler_type));
    }
    $definition->setHandlerClass('_access', $_class);
    $class = 'Drupal\pach\Entity\EntityAccessControlHandler';
    $this->handlers[$handler_type][$entity_type] = $this->createHandlerInstance($class, $definition);
    return $this->handlers[$handler_type][$entity_type];
  }

  /**
   * {@inheritdoc}
   */
  public function createHandlerInstance($class, EntityTypeInterface $definition = NULL) {
    if (is_subclass_of($class, 'Drupal\Core\Entity\EntityHandlerInterface')) {
      $handler = $class::createInstance($this->container, $definition);
    }
    elseif (is_subclass_of($class, 'Drupal\pach\Entity\PluggableAccessControlHandlerInterface')) {
      $handler = $class::createInstance($this->container, $definition, $this);
    }
    else {
      $handler = new $class($definition);
    }
    if (method_exists($handler, 'setModuleHandler')) {
      $handler->setModuleHandler($this->moduleHandler);
    }
    if (method_exists($handler, 'setStringTranslation')) {
      $handler->setStringTranslation($this->stringTranslation);
    }

    return $handler;
  }

}
