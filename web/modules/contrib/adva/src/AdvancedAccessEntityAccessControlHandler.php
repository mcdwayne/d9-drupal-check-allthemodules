<?php

namespace Drupal\adva;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the node entity type.
 *
 * @see \Drupal\node\Entity\Node
 * @ingroup node_access
 */
class AdvancedAccessEntityAccessControlHandler extends EntityAccessControlHandler implements AdvancedAccessEntityAccessControlHandlerInterface {

  const LEGACY_HANDLER_ID = "adva_access_legacy";

  /**
   * The node grant storage.
   *
   * @var \Drupal\adva\AccessStorage
   */
  protected $accessStorage;

  /**
   * The node grant storage.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $legacyHandler;

  /**
   * The plugin manager for access consumer plugins.
   *
   * @var \Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface
   */
  protected $consumerManager;

  /**
   * Access Consumer plugin for the entity type.
   *
   * @var \Drupal\adva\Annotation\AccessConsumerInterface
   */
  private $consumer;

  /**
   * Constructs a NodeAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\adva\AccessStorage $access_storage
   *   Advanced Access record storage.
   * @param \Drupal\Core\Entity\EntityAccessControlHandlerInterface $legacy_handler
   *   Legacy Access control handler if one was replaced.
   */
  public function __construct(EntityTypeInterface $entity_type, AccessStorage $access_storage, EntityAccessControlHandlerInterface $legacy_handler = NULL) {
    parent::__construct($entity_type);
    $this->accessStorage = $access_storage;
    $this->legacyHandler = $legacy_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {

    // Load the legacy, overridden, access handler if one is present.
    $legacy_handler = NULL;
    if ($entity_type->hasHandlerClass(static::LEGACY_HANDLER_ID)) {
      $legacy_handler = $container->get('entity_type.manager')->getHandler($entity_type->id(), static::LEGACY_HANDLER_ID);
    }
    $access_storage = $container->get("adva.access_storage");

    return new static(
      $entity_type,
      $access_storage,
      $legacy_handler
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $access = AccessResult::neutral();
    if ($this->legacyHandler) {
      $access = $access->orIf($this->legacyHandler->access($entity, $operation, $account, TRUE));
    }
    else {
      $access = $access->orIf(parent::access($entity, $operation, $account, TRUE));
    }
    if (!$access->isForbidden()) {
      $access = $access->orIf($this->accessStorage->access($entity, $operation, $account));
    }
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $access = AccessResult::neutral();
    if ($this->legacyHandler) {
      $access = $access->orIf($this->legacyHandler->createAccess($entity_bundle, $account, $context, TRUE));
    }
    else {
      $access = $access->orIf(parent::createAccess($entity_bundle, $account, $context, TRUE));
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    $access = AccessResult::neutral();
    if ($this->legacyHandler) {
      $access = $access->orIf($this->legacyHandler->checkAccess($entity, $operation, $account));
    }
    else {
      $access = $access->orIf(parent::checkAccess($entity, $operation, $account));
    }
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $account = $this->prepareUser($account);
    $access = AccessResult::neutral();
    if ($this->legacyHandler) {
      $access = $access->orIf($this->legacyHandler->checkFieldAccess($operation, $field_definition, $account, $items));
    }
    else {
      $access = $access->orIf(parent::checkFieldAccess($operation, $field_definition, $account, $items));
    }
    return $access;
  }

}
