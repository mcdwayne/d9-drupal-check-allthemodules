<?php

namespace Drupal\jsonrpc_core\Plugin\jsonrpc\Method;

use Drupal\jsonrpc\Object\ParameterBag;

/**
 * RPC method to list all the permissions.
 *
 * @JsonRpcMethod(
 *   id = "user_permissions.list",
 *   usage = @Translation("List all the permissions available in the site."),
 *   access = {"administer permissions"},
 *   params = {
 *     "page" = @JsonRpcParameterDefinition(factory = "\Drupal\jsonrpc\ParameterFactory\PaginationParameterFactory"),
 *   }
 * )
 */
class ListPermissions extends UserPermissionsBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $params) {
    $page = $params->get('page');
    return array_slice(
      $this->permissions->getPermissions(),
      $page['offset'],
      $page['limit']
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function outputSchema() {
    // TODO: Fix the schema.
    return ['type' => 'foo'];
  }

}
