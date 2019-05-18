<?php

namespace Drupal\lti_tool_provider;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class LtiToolProviderProvisionAccessController extends EntityAccessControlHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        return AccessResult::allowedIfHasPermission($account, 'administer lti_tool_provider module');
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = null)
    {
        return AccessResult::allowedIfHasPermission($account, 'administer lti_tool_provider module');
    }
}
