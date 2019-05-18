<?php

namespace Drupal\rac_relations\Plugin\adva\AccessProvider;

use Drupal\adva\Plugin\adva\ReferenceAccessProvider;

use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;

/**
 * Role Access Provider for Advanced Access.
 *
 * The role access control relations provider is similar to the rac provider, it
 * however used the role mapping which allows a user to view/update content
 * based on related roles.
 *
 * @AccessProvider(
 *   id = "rac_relations",
 *   label = @Translation("Role Access Control Relations"),
 *   operations = {
 *     "view",
 *     "update",
 *     "delete",
 *   },
 * )
 */
class RoleAccessControlRelationsProvider extends ReferenceAccessProvider {

  /**
   * {@inheritdoc}
   */
  public static function getTargetType() {
    return "user_role";
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizedEntityIds($operation, AccountInterface $account) {
    $userRoles = _rac_get_account_roles('update', $account);
    // Check the user update permissions, and then return the authorized ids.
    return array_map(
      function ($role) {
        return $role->id();
      },
      $userRoles
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getHelperMessage(array $definition) {
    $context = [
      '%provider' => $definition['label'],
    ];
    $context['%link'] = Link::createFromRoute($definition['label'] . ' settings', 'rac_relations.settings', [], $context)->toString();

    $message = '<p>' . \Drupal::translation()->translate('<em>%provider</em> allows a user to access control content based upon role relations. You can configure the access relations in the %link.', $context) . '</p>';
    $message .= '<p>' . parent::getHelperMessage($definition) . '</p>';
    return $message;
  }

}
