<?php

namespace Drupal\aws;

use Drupal\aws\Entity\Profile;

/**
 * Undocumented class.
 */
class Aws {

  /**
   * Returns a profile for the given service.
   *
   * @param string $service
   *   The service.
   *
   * @return Drupal\aws\Entity\ProfileInterface
   *   The profile for the service.
   */
  public function getProfile($service) {
    $service_config = $this->getAwsServiceConfig($service);
    $profile_id = $service_config->get('profile');
    if (empty($profile_id)) {
      return $this->getDefaultProfile();
    }
    return Profile::load($profile_id);
  }

  /**
   * Returns the default profile.
   *
   * @return \Drupal\aws\Entity\ProfileInterface
   *   The default profile.
   */
  public function getDefaultProfile() {
    $query = \Drupal::entityQuery('aws_profile');
    $query->condition('default', TRUE);
    $ids = $query->execute();
    $profile_id = reset($ids);
    return Profile::load($profile_id);
  }

  /**
   * Undocumented function.
   *
   * @param string $service
   *   The service.
   */
  public function getAwsServiceConfig($service) {
    $config = sprintf('aws.%s.settings', $service);
    $config_factory = \Drupal::service('config.factory');
    return $config_factory->get($config);
  }

  /**
   * Retrieves all available profiles.
   *
   * @return \Drupal\aws\Entity\ProfileInterface[]
   *   An array of Profile objects.
   */
  public function getProfiles() {
    $query = \Drupal::entityQuery('aws_profile');
    $ids = $query->execute();

    $profiles = [];
    foreach ($ids as $id) {
      $profiles[] = Profile::load($id);
    }
    return $profiles;
  }

  /**
   * Returns the service definition for the provided service id.
   *
   * @param string $service_id
   *   The service ID.
   *
   * @return array
   *   The service definition.
   */
  public function getService($service_id) {
    $plugin_manager = \Drupal::service('plugin.manager.aws_service');
    return $plugin_manager->getDefinition($service_id);
  }

  /**
   * Retrieves all registered AWS Services.
   *
   * @return array
   *   An array of AWS Service definitions.
   */
  public function getServices() {
    $plugin_manager = \Drupal::service('plugin.manager.aws_service');
    return $plugin_manager->getDefinitions();
  }

}
