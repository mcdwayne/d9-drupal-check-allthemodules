<?php

namespace Drupal\media_entity_consent;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Some global functions that are needed for media_entity_consent.
 */
class ConsentHelper {

  const CONSENT_PREFIX = 'media-entity-consent--';

  /**
   * Gets the user roles.
   *
   * @return array
   *   All the roles of the user.
   */
  public static function getUserRoles() {
    $entityTypeManager = \Drupal::entityTypeManager();
    $roles = [];

    try {
      $roles = $entityTypeManager->getStorage('user_role')->loadMultiple();
    }
    catch (InvalidPluginDefinitionException $exception) {
      \Drupal::logger('media_entity_consent')->error($exception->getMessage());
    }
    catch (PluginNotFoundException $exception) {
      \Drupal::logger('media_entity_consent')->error($exception->getMessage());
    }

    return $roles;
  }

  /**
   * Gets the media types.
   *
   * @return array
   *   All the media types available.
   */
  public static function getMediaTypes() {
    $entityTypeManager = \Drupal::entityTypeManager();
    $media_types = [];

    try {
      $media_types = $entityTypeManager->getStorage('media_type')->loadMultiple();
    }
    catch (InvalidPluginDefinitionException $exception) {
      \Drupal::logger('media_entity_consent')->error($exception->getMessage());
    }
    catch (PluginNotFoundException $exception) {
      \Drupal::logger('media_entity_consent')->error($exception->getMessage());
    }

    return $media_types;
  }

  /**
   * Gets the media display modes.
   *
   * @return array
   *   All the media display modes available.
   */
  public static function getDisplayModes() {
    $display_modes = \Drupal::entityQuery('entity_view_mode')
      ->condition('targetEntityType', 'media')
      ->execute();

    foreach ($display_modes as &$display_mode) {
      $display_mode = str_replace('media.', '', $display_mode);
    }

    return $display_modes;
  }

  /**
   * Check if user is allowed to bypass media entity consent.
   *
   * @return bool
   *   Indication, if the user is allowed to bypass.
   */
  public static function userCanBypass() {
    $config = \Drupal::config('media_entity_consent.settings');
    $roles_map = $config->get('access_bypass');
    $bypass = FALSE;
    $user_roles = \Drupal::currentUser()->getRoles(FALSE);

    foreach ($roles_map as $id => $flag) {
      if ($flag && in_array($id, $user_roles)) {
        $bypass = TRUE;
      }
    }
    return $bypass;
  }

  /**
   * Get the configured external libraries.
   *
   * @return array
   *   List of external libraries
   */
  public static function identifyExternalLibraries() {
    $config = \Drupal::config('media_entity_consent.settings');
    $excluded_config = [];

    foreach ((array) $config->get('media_types') as $media_type => $value) {
      $libs = preg_split('/$\R?^/m', str_replace("\r", "", $value['excluded_files']));
      if (isset($libs[0]) && !empty($libs[0])) {
        $excluded_config[$media_type] = $libs;
      }
    }
    return $excluded_config;
  }

  /**
   * Get the user's media_entity_consent cookies.
   *
   * @return array
   *   A key-value pair of the media types and their cookie status.
   */
  public static function getConsentCookies() {
    $cookies = [];
    foreach ($_COOKIE as $name => $value) {
      if (strpos($name, self::CONSENT_PREFIX) !== FALSE) {
        $cookies[str_replace('Drupal_visitor_' . self::CONSENT_PREFIX, '', $name)] = (bool) $value;
      }
    }
    return $cookies;
  }

  /**
   * SET the user's media_entity_consent cookie.
   *
   * @param string $media_type
   *   The media type the cookie is for. Will determine cookie name.
   * @param bool $value
   *   The value to be set. If it is false, the cookie will be deleted.
   */
  public static function setConsentCookie(string $media_type, bool $value) {
    if ($value) {
      user_cookie_save([self::CONSENT_PREFIX . $media_type => TRUE]);
    }
    else {
      user_cookie_delete(self::CONSENT_PREFIX . $media_type);
    }
  }

  /**
   * Check if user has given consent for certain media_type.
   *
   * @return bool
   *   Weather the user has already given consent.
   */
  public static function userHasGivenConsent($media_type) {
    $consents = self::getConsentCookies();
    if (in_array($media_type, $consents) && $consents[$media_type] == TRUE) {
      return TRUE;
    }
    return FALSE;
  }

}
