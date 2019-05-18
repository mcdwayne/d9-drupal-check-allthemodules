<?php

namespace Drupal\pach\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\pach\AccessControlHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a pluggable implementation for entity access control handler.
 */
class EntityAccessControlHandler extends CoreEntityAccessControlHandler implements PluggableAccessControlHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The original access handler instance.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandlerOriginal;

  /**
   * The access control handler plugin manager.
   *
   * @var \Drupal\pach\AccessControlHandlerManager
   */
  protected $pluginManager;

  /**
   * List of applicable access control handlers.
   *
   * @var \Drupal\pach\Plugin\AccessControlHandlerInterface[]
   */
  protected $handlers = [];

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, AccessControlHandlerManager $plugin_manager, SerializationInterface $serializer) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entity_type_manager;
    $this->serializer = $serializer;

    if (!$this->entityType->hasHandlerClass('_access')) {
      throw new InvalidPluginDefinitionException($this->entityTypeId, sprintf('The "%s" entity type did not specify a %s handler.', $this->entityTypeId, '_access'));
    }
    $this->accessHandlerOriginal = $this->entityTypeManager->getHandler($this->entityTypeId, '_access');
    $this->pluginManager = $plugin_manager;
    $this->handlers = $this->pluginManager->getHandlers($this->entityTypeId);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    return new static(
      $entity_type,
      $entity_type_manager,
      $container->get('plugin.manager.pach'),
      $container->get('serialization.phpserialize')
    );
  }

  /**
   * Call functions of original access control handlers.
   *
   * @param string $name
   *   Name of function.
   * @param array $arguments
   *   Arguments for original function.
   *
   * @return mixed
   *   Return value of original function.
   *
   * @throws \Exception
   *   A generic exception is thrown if the called method does not exist in
   *   original entity access control handler.
   */
  public function __call($name, array $arguments) {
    if (!method_exists($this->accessHandlerOriginal, $name)) {
      throw new \Exception(sprintf('Method "%s" does not exists for class "%s".', [$name, get_class($this->accessHandlerOriginal)]));
    }
    return call_user_func_array([$this->accessHandlerOriginal, $name], $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $langcode = $entity->language()->getId();
    $account = $this->prepareUser($account);

    if ($operation === 'view label' && $this->viewLabelOperation == FALSE) {
      $operation = 'view';
    }

    if (($return = $this->getCache($entity->uuid(), $operation, $langcode, $account)) !== NULL) {
      // Cache hit, no work necessary.
      return $return_as_object ? $return : $return->isAllowed();
    }

    $return = $this->accessHandlerOriginal->access($entity, $operation, $account, TRUE);

    // Process plugins.
    foreach ($this->handlers as $handler) {
      if (!$handler->applies($entity, $operation, $account) || !method_exists($handler, 'access')) {
        continue;
      }
      $handler->access($return, $entity, $operation, $account);
    }

    $result = $this->setCache($return, $entity->uuid(), $operation, $langcode, $account);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $context += array(
      'entity_type_id' => $this->entityTypeId,
      'langcode' => LanguageInterface::LANGCODE_DEFAULT,
    );

    // @todo This cache ID handling has to be updated when https://www.drupal.org/node/2886800 is fixed.

    // Prepare context array for serialization.
    ksort($context);

    $cid = ($entity_bundle ? 'create:' . $entity_bundle : 'create') . ':' . md5($this->serializer->encode($context));
    if (($access = $this->getCache($cid, 'create', $context['langcode'], $account)) !== NULL) {
      // Cache hit, no work necessary.
      return $return_as_object ? $access : $access->isAllowed();
    }

    $return = $this->accessHandlerOriginal->createAccess($entity_bundle, $account, $context, TRUE);

    // Process plugins.
    foreach ($this->handlers as $handler) {
      if (!method_exists($handler, 'createAccess')) {
        continue;
      }
      $handler->createAccess($return, $entity_bundle, $account, $context);
    }

    $result = $this->setCache($return, $cid, 'create', $context['langcode'], $account);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $return = parent::fieldAccess($operation, $field_definition, $account, $items, TRUE);

    // Process plugins.
    foreach ($this->handlers as $handler) {
      if (!method_exists($handler, 'fieldAccess')) {
        continue;
      }
      $handler->fieldAccess($return, $operation, $field_definition, $account, $items);
    }

    return $return_as_object ? $return : $return->isAllowed();
  }

}
