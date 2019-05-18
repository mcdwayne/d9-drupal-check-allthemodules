<?php

namespace Drupal\janrain_connect_admin_services\Service;

use Drupal\Component\Utility\Unicode;
use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;

/**
 * Class JanrainConnectPluralHelper.
 *
 * @package Drupal\janrain_connect_admin_services\Service
 */
class JanrainConnectPluralHelper {

  /**
   * Janrain admin service calls.
   *
   * @var \Drupal\janrain_connect_admin_services\Service\JanrainConnectAdminServicesCalls
   */
  private $janrain;

  /**
   * JanrainConnectPluralHelper constructor.
   *
   * @param JanrainConnectAdminServicesCalls $janrain_admin_service_call
   *   Janrain connector object.
   */
  public function __construct(JanrainConnectAdminServicesCalls $janrain_admin_service_call) {
    $this->janrain = $janrain_admin_service_call;
  }

  /**
   * Get plural by user email.
   *
   * @param string $field_name
   *   Field name.
   * @param string $filter_user
   *   Filter user.
   * @param array $plural
   *   Array containing key and value of the plural.
   *
   * @return array|bool
   *   False if we dont find our plural; The array withing the plurals.
   */
  public function getPlural($field_name, $filter_user, array $plural = []) {
    $user = $this->janrain->findUser($filter_user);

    if (FALSE === $user ||
      !isset($user[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_RESULTS][0]) ||
      !property_exists($user[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_RESULTS][0], $field_name)) {
      return FALSE;
    }

    $plurals = [];
    foreach ($user[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_RESULTS][0]->{$field_name} as $item) {
      $plurals[] = JanrainConnectPluralHelper::getMatchPlural((array) $item, $plural);
    }
    return array_values(array_filter($plurals));
  }

  /**
   * Get a match plural.
   *
   * @param array $fields
   *   Fields containing plurals.
   * @param array $plural
   *   Plurals values.
   *
   * @return array|bool
   *   Field that matches the item or FALSE.
   */
  private static function getMatchPlural(array $fields, array $plural) {
    $keys = array_keys($plural);
    foreach ($keys as $key_name) {
      if (isset($fields[$key_name]) && Unicode::strcasecmp($fields[$key_name], $plural[$key_name]) == 0) {
        return $fields;
      }
    }
    return FALSE;
  }

}
