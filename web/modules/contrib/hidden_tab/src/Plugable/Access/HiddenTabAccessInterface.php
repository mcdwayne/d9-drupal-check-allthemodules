<?php

namespace Drupal\hidden_tab\Plugable\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Plugin to grant access to hidden tab entities based on some context.
 */
interface HiddenTabAccessInterface extends HiddenTabPluginInterfaceBase {

  const PID = 'hidden_tab_access';

  /**
   * A decision, access or deny, or neutral.
   *
   * @param \Drupal\Core\Entity\EntityInterface $context_entity
   *   The entity being acted upon.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The acting user.
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface|null $page
   *   The page being access, if any.
   * @param \Symfony\Component\HttpFoundation\ParameterBag $query
   *   Current query.
   * @param string $operation
   *   Requested operation on the entity.
   *
   * @return \Drupal\Core\Access\AccessResultReasonInterface
   *   What the plugin has decided. What the plugin has decided.
   */
  public function canAccess(EntityInterface $context_entity,
                            AccountInterface $account,
                            ?HiddenTabPageInterface $page,
                            ParameterBag $query,
                            string $operation): AccessResult;

}
