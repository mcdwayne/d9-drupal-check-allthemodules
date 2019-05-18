<?php

namespace Drupal\global_gateway\Plugin\RegionNegotiation;

use Drupal\global_gateway\RegionNegotiationTypeBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from the user preferences.
 *
 * @RegionNegotiation(
 *   id = "session",
 *   weight = -5,
 *   name = @Translation("Session"),
 *   description = @Translation("Get region code from session.")
 * )
 */
class RegionNegotiationSession extends RegionNegotiationTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getRegionCode(Request $request = NULL) {
    $user = \Drupal::currentUser();

    if ($user->isAuthenticated()) {
      $region_code = \Drupal::service('user.data')->get('global_gateway', $user->id(), 'current_region');
    }
    // If user is Anonymous get region code from session.
    else {
      $region_code = \Drupal::service('user.private_tempstore')->get('current_region');
    }

    /** @var \Drupal\global_gateway\DisabledRegionsProcessor $processor */
    $processor = \Drupal::service('global_gateway.disabled_regions.processor');
    if ($region_code != 'none'
      && !is_null($region_code)
      && $processor->isDisabled($region_code)
    ) {
      $region_code = $processor->getFallbackRegionCode($region_code);
    }

    return $region_code == 'none' ? FALSE : $region_code;
  }

  /**
   * Set region code for the current user.
   *
   * @param string $region_code
   *   Region code which need to save.
   */
  public function persist($region_code) {
    $user = \Drupal::currentUser();
    if ($user->isAuthenticated()) {
      \Drupal::service('user.data')->set('global_gateway', $user->id(), 'current_region', $region_code);
    }
    else {
      if (!isset($_SESSION['session_started'])) {
        $_SESSION['session_started'] = TRUE;
        \Drupal::service('session_manager')->start();
      }
      \Drupal::service('user.private_tempstore')->set('current_region', $region_code);
    }
  }

}
