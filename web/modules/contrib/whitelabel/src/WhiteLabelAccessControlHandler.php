<?php

namespace Drupal\whitelabel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the white label entity.
 *
 * @see \Drupal\comment\Entity\WhiteLabel.
 */
class WhiteLabelAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('administer white label settings')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    $result = parent::access($entity, $operation, $account, TRUE)->cachePerPermissions();

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('administer white label settings')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    $result = parent::createAccess($entity_bundle, $account, $context, TRUE)->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $whitelabel, $operation, AccountInterface $account = NULL) {
    /* @var \Drupal\whitelabel\WhiteLabelInterface $whitelabel */

    // Owner can view and update with the right permissions.
    $uid = $whitelabel->getOwnerId();
    if (($operation == 'view' || $operation == 'update' || $operation == 'serve') && !empty($account) && $account->id() == $uid) {
      return AccessResult::allowedIfHasPermission($account, 'serve white label pages')->cachePerPermissions()->cachePerUser()->addCacheableDependency($whitelabel);
    }

    // View access is independent of the white label, no cacheable dependency.
    if ($operation == 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view white label pages')->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account = NULL, array $context = [], $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'serve white label pages')->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // Load white label configuration.
    $config = \Drupal::config('whitelabel.settings');

    if ($operation == 'view' || $operation == 'edit') {
      // White label tokens are always allowed.
      if ($field_definition->getName() == 'token') {
        return AccessResult::allowed()->setCacheMaxAge(0);
      }

      // Other fields are checked one by one.
      $fields = [
        'name_display',
        'name',
        'slogan',
        'logo',
        'theme',
      ];

      if (in_array($field_definition->getName(), $fields)) {
        if ($config->get('site_' . $field_definition->getName()) === TRUE) {
          return AccessResult::allowed()->addCacheableDependency($config);
        }
        else {
          return AccessResult::forbidden('Field not enabled in configuration.')->addCacheableDependency($config);
        }
      }
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
