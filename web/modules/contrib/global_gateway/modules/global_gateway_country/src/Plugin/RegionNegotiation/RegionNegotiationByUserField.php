<?php

namespace Drupal\global_gateway_country\Plugin\RegionNegotiation;

use Drupal\global_gateway\RegionNegotiationTypeBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RegionNegotiationByUserField.
 *
 * Country detection method which sets the default value using a value from
 * the field of current User entity.
 * Field name is specified in method configuration form.
 *
 * @RegionNegotiation(
 *   id = "user_field",
 *   weight = -5,
 *   name = @Translation("By user field value"),
 *   description = @Translation("Use region code from users field."),
 *   config_route_name = "global_gateway_country.negotiation_by_user_field"
 * )
 *
 * @package Drupal\global_gateway_country\Plugin\RegionNegotiation
 */
class RegionNegotiationByUserField extends RegionNegotiationTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getRegionCode(Request $request = NULL) {
    // Get field name specified in detection method config form.
    $field_name = $this->get('user_field_name');
    /** @var \Drupal\user\Entity\User $user */
    // Load the current user entity.
    $user = User::load(\Drupal::currentUser()->id());
    // Ensure we have specified field in current entity.
    if (!$user->hasField($field_name)) {
      return FALSE;
    }
    $field = $user->get($field_name);
    // Return region code if exist, false otherwise.
    return !$field->isEmpty() ? $field->get(0)->getValue()['value'] : FALSE;
  }

}
