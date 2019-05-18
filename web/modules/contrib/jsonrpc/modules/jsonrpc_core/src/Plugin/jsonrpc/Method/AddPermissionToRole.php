<?php

namespace Drupal\jsonrpc_core\Plugin\jsonrpc\Method;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\jsonrpc\Exception\JsonRpcException;
use Drupal\jsonrpc\Object\Error;
use Drupal\jsonrpc\Object\ParameterBag;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * A method to add permissions to a role.
 *
 * @JsonRpcMethod(
 *   id = "user_permissions.add_permission_to_role",
 *   usage = @Translation("Add the given permission to the specified role."),
 *   access = {"administer permissions"},
 *   params = {
 *     "permission" = @JsonRpcParameterDefinition(schema = {"type": "string"}),
 *     "role" = @JsonRpcParameterDefinition(factory = "\Drupal\jsonrpc\ParameterFactory\EntityParameterFactory"),
 *   }
 * )
 */
class AddPermissionToRole extends UserPermissionsBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $params) {
    $permission = $params->get('permission');
    /* @var \Drupal\user\RoleInterface $role */
    $role = $params->get('role');
    try {
      $role->grantPermission($permission);
      $violations = $role->getTypedData()->validate();
      if ($violations->count() !== 0) {
        $error = Error::invalidParams(array_map(function (ConstraintViolationInterface $violation) {
          return $violation->getMessage();
        }, iterator_to_array($violations)));
        throw JsonRpcException::fromError($error);
      }
      return $role->save();
    }
    catch (EntityStorageException $e) {
      $error = Error::internalError('Unable to save the user role. Error: ' . $e->getMessage(), $role);
      throw JsonRpcException::fromError($error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function outputSchema() {
    return ['type' => 'number'];
  }

}
