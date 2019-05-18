<?php

namespace Drupal\akismet\Utility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\akismet\Entity\FormInterface;

class AkismetUtilities {

  /**
   * Recursive helper function to flatten nested form values.
   *
   * Takes a potentially nested array and returns all non-empty string values in
   * nested keys as new indexed array.
   */
  public static function flattenFormValue($values) {
    $flat_values = [];
    foreach ($values as $value) {
      if (is_array($value)) {
        // Only text fields are supported at this point; their values are in the
        // 'summary' (optional) and 'value' keys.
        if (isset($value['value'])) {
          if (isset($value['summary']) && $value['summary'] !== '') {
            $flat_values[] = $value['summary'];
          }
          if ($value['value'] !== '') {
            $flat_values[] = $value['value'];
          }
        }
        elseif (!empty($value)) {
          $flat_values = array_merge($flat_values, self::flattenFormValue($value));
        }
      }
      elseif (is_string($value) && strlen($value)) {
        $flat_values[] = $value;
      }
    }
    return $flat_values;
  }

  /**
   * Helper function to determine protected forms for an entity.
   *
   * @param $type
   *   The type of entity to check.
   * @param $bundle
   *   An array of bundle names to check.
   *
   * @return array
   *   An array of protected bundles for this entity type.
   */
  public static function _akismet_get_entity_forms_protected($type, $bundles = array()) {
    // Find out if this entity bundle is protected.
    $protected = &drupal_static(__FUNCTION__, array());
    if (empty($bundles)) {
      $info = entity_get_info($type);
      $bundles = array_keys($info['bundles']);
    }
    $protected_bundles = array();
    foreach ($bundles as $bundle) {
      if (!isset($protected[$type][$bundle])) {
        $protected[$type][$bundle] = db_query_range('SELECT 1 FROM {akismet_form} WHERE entity = :entity AND bundle = :bundle', 0, 1, array(
          ':entity' => $type,
          ':bundle' => isset($bundle) ? $bundle : $type,
        ))->fetchField();
      }
      if (!empty($protected[$type][$bundle])) {
        $protected_bundles[] = $bundle;
      }
    }
    return $protected_bundles;
  }

  /**
   * Returns the (last known) status of the configured Akismet API keys.
   *
   * @param bool $force
   *   (optional) Boolean whether to ignore the cached state and re-check.
   *   Defaults to FALSE.
   * @param bool $update
   *   (optional) Whether to update Akismet with locally stored configuration.
   *   Defaults to FALSE.
   *
   * @return array
   *   An associative array describing the current status of the module:
   *   - isConfigured: Boolean whether Akismet API keys have been configured.
   *   - isVerified: Boolean whether Akismet API keys have been verified.
   *   - response: The response error code of the API verification request.
   *   - ...: The full site resource, as returned by the Akismet API.
   *
   * @see akismet_requirements()
   */
  public static function getAPIKeyStatus($force = FALSE, $update = FALSE) {
    $testing_mode = (int) \Drupal::config('akismet.settings')
      ->get('test_mode.enabled');
    /*
    $static_cache = &drupal_static(__FUNCTION__, array());
    $status = &$static_cache[$testing_mode];

    $drupal_cache = \Drupal::cache();
    $cid = 'akismet_status:' . $testing_mode;
    $expire_valid = 86400; // once per day
    $expire_invalid = 3600; // once per hour

    // Look for cached status.
    if (!$force) {
      if (isset($status)) {
        return $status;
      }
      else if ($cache = $drupal_cache->get($cid)) {
        return $cache->data;
      }
    }*/

    // Re-check configuration status.
    /** @var \Drupal\akismet\Client\DrupalClient $akismet */
    $akismet = \Drupal::service('akismet.client');
    $status = array(
      'isConfigured' => FALSE,
      'isVerified' => FALSE,
      'isTesting' => (bool) $testing_mode,
      'response' => NULL,
      'key' => $akismet->loadConfiguration('key'),
    );
    $status['isConfigured'] = !empty($status['key']);

    if ($testing_mode || $status['isConfigured']) {
      $response = $akismet->verifyKey($status['key']);

      if ($response === TRUE) {
        $status['isVerified'] = TRUE;
        Logger::addMessage(array(
          'message' => 'API key is valid.',
        ), RfcLogLevel::INFO);
      }
      elseif ($response === $akismet::AUTH_ERROR) {
        $status['response'] = $response;
        Logger::addMessage(array(
          'message' => 'Invalid API key.',
        ), RfcLogLevel::ERROR);
      }
      elseif ($response === $akismet::REQUEST_ERROR) {
        $status['response'] = $response;
        Logger::addMessage(array(
          'message' => 'Invalid client configuration.',
        ), RfcLogLevel::ERROR);
      }
      else {
        $status['response'] = $response;
        // A NETWORK_ERROR and other possible responses may be caused by the
        // client-side environment, but also by Akismet service downtimes. Try to
        // recover as soon as possible.
        $expire_invalid = 60 * 5;
        Logger::addMessage(array(
          'message' => 'API keys could not be verified.',
        ), RfcLogLevel::ERROR);
      }
    }
    //$drupal_cache->set($cid, $status, $status['isVerified'] === TRUE ? $expire_valid : $expire_invalid);
    return $status;
  }

  /**
   * Gets the status of Akismet's API key configuration and also displays a
   * warning message if the Akismet API keys are not configured.
   *
   * To be used within the Akismet administration pages only.
   *
   * @param bool $force
   *   (optional) Boolean whether to ignore the cached state and re-check.
   *   Defaults to FALSE.
   * @param bool $update
   *   (optional) Whether to update Akismet with locally stored configuration.
   *   Defaults to FALSE.
   *
   * @return array
   *   An associative array describing the current status of the module:
   *   - isConfigured: Boolean whether Akismet API keys have been configured.
   *   - isVerified: Boolean whether Akismet API keys have been verified.
   *   - response: The response error code of the API verification request.
   *   - ...: The full site resource, as returned by the Akismet API.
   *
   * @see Akismet::getAPIKeyStatus().
   */
  public static function getAdminAPIKeyStatus($force = FALSE, $update = FALSE) {
    $status = AkismetUtilities::getAPIKeyStatus($force, $update);
    if (empty($_POST) && !$status['isVerified']) {
      // Fetch and display requirements error message, without re-checking.
      module_load_install('akismet');
      $requirements = akismet_requirements('runtime', FALSE);
      if (isset($requirements['akismet']['description'])) {
        \Drupal::messenger()->addMessage($requirements['akismet']['description'], 'error');
      }
    }
    return $status;
  }


  /**
   * Outputs a warning message about enabled testing mode (once).
   */
  public static function displayAkismetTestModeWarning() {
    // Messenger::addMessage() starts a session and disables page caching, which
    // breaks cache-related tests. Thus, tests set the verbose variable to TRUE.
    if (\Drupal::state()->get('akismet.omit_warning') ?: FALSE) {
      return;
    }

    if (\Drupal::config('akismet.settings')->get('test_mode.enabled') && empty($_POST)) {
      $admin_message = '';
      if (\Drupal::currentUser()
          ->hasPermission('administer akismet') && \Drupal::routeMatch()
          ->getRouteName() != 'akismet.settings'
      ) {
        $admin_message = t('Visit the <a href="@settings-url">Akismet settings page</a> to disable it.', array(
          '@settings-url' => Url::fromRoute('akismet.settings')->toString(),
        ));
      }
      $message = t('Akismet testing mode is still enabled. @admin-message', array(
        '@admin-message' => $admin_message,
      ));
      \Drupal::messenger()->addMessage($message, 'warning', FALSE);
    }
  }

  /**
   * Helper function to log and optionally output an error message when Akismet servers are unavailable.
   */
  public static function handleFallback(FormStateInterface $form_state = NULL, $element_name = '') {
    $fallback = \Drupal::config('akismet.settings')->get('fallback');
    if ($fallback == FormInterface::AKISMET_FALLBACK_BLOCK) {
      $block_message = t("The spam filter installed on this site is currently unavailable. Per site policy, we are unable to accept new submissions until that problem is resolved. Please try resubmitting the form in a couple of minutes.");
      \Drupal::messenger()->addMessage($block_message, 'error');
      if (!empty($form_state) && !empty($element_name)) {
        $form_state->setErrorByName($element_name, $block_message);
      }
    }
    return TRUE;
  }
}
