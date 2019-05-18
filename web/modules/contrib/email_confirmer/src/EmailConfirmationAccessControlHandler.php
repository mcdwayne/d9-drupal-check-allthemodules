<?php

namespace Drupal\email_confirmer;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityHandlerInterface;

/**
 * Access controller for the email confirmation entity.
 *
 * @see \Drupal\email_confirmer\Entity\EmailConfirmation.
 */
class EmailConfirmationAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The email confirmer config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $confirmerConfig;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates a new EmailConfirmationAccessControlHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    parent::__construct($entity_type);

    $this->confirmerConfig = $config_factory->get('email_confirmer.settings');
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type, $container->get('config.factory'), $container->get('request_stack'));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\email_confirmer\EmailConfirmationInterface $entity */
    if ($account->hasPermission('administer email confirmations')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // IP access restriction.
    if ($this->confirmerConfig->get('restrict_same_ip')
      && !$entity->get('ip')->isEmpty()
      && $entity->get('ip')->getString() != $this->requestStack->getCurrentRequest()->getClientIp()) {
      return AccessResult::forbidden()->addCacheContexts(['ip'])->addCacheTags($entity->getCacheTags());
    }

    // Private confirmation access restriction.
    if ($entity->isPrivate()
      && !in_array($entity->get('uid')->target_id, [0, $account->id()])) {
      return AccessResult::forbidden()->cachePerUser()->addCacheTags($entity->getCacheTags());
    }

    return AccessResult::allowedIfHasPermission($account, 'access email confirmation')->cachePerPermissions()->addCacheTags($entity->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'access email confirmation')->cachePerPermissions();
  }

}
