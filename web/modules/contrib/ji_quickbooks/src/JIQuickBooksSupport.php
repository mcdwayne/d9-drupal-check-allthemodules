<?php

namespace Drupal\ji_quickbooks;

use Drupal\Core\Url;

/**
 * Support Class.
 *
 * Contains common methods used throughout the JI QuickBooks
 * module.
 */
class JIQuickBooksSupport {

  /**
   * SDK download location.
   *
   * @var string
   */
  public static $sdkUrl = 'https://ji-quickbooks.joshideas.com/sdk/qbosdk3260.zip';

  /**
   * The OpenID application that communicates with QBO.
   *
   * @var string
   */
  public static $oAuthUrl = 'https://ji-quickbooks-v3.joshideas.com';

  /**
   * Name of the cached table.
   *
   * @var string
   */
  public static $taxAgencies = 'ji_quickbooks_tax_agencies';

  /**
   * Caches QBO responses since ji_autocomplete makes multiple calls.
   *
   * Probably the coolest function on the planet! Prevents
   * multiple calls to QBO and instead caches results (db storage)
   * which improves performance.
   */
  public static function taxAgenciesCache() {
    $option_agencies = &drupal_static(__FUNCTION__);
    if (!isset($option_agencies)) {
      $cache = \Drupal::cache()->get(self::$taxAgencies);
      if ($cache) {
        $option_agencies = $cache->data;
      }
      else {
        $options = [];

        try {
          $quickbooks_service = new JIQuickBooksService();
          $response = $quickbooks_service->getTaxAgencies();
          $error = $quickbooks_service->checkErrors();
          if (!empty($error['code'])) {
            return TRUE;
          }

          foreach ($response as $agency) {
            $options[$agency->Id] = $agency->DisplayName;
          }
        } catch (\Exception $e) {
          \Drupal::logger('ji_quickbooks')
            ->critical(t('Attempting to cache tax agencies failed, exception thrown: @e', ['@e' => $e->getMessage()]));
          return TRUE;
        }

        \Drupal::cache()->set(self::$taxAgencies, $options);

        return $options;
      }
    }
    return $option_agencies;
  }

  /**
   * Checks against common errors.
   *
   * @return bool
   *   FALSE if no errors true otherwise.
   */
  public static function checkForCommonErrors() {
    // If OAuth isn't installed, don't allow our functions to run since it will
    // crash general functionality.
    $oauth = self::checkForOauth(TRUE);
    if (!$oauth) {
      return TRUE;
    }

    // If the user forgets to adjust the settings, inform them
    // if user is an admin and return.
    // Prevents the system from crashing execution.
    $settings_set = self::checkIfSettingsApplied(TRUE);
    if (!$settings_set) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if all settings for JI QuickBooks are set.
   *
   * Returns boolean false if even one of the settings
   * haven't been applied.
   */
  public static function checkIfSettingsApplied($show_message = FALSE) {
    $settings_count = 0;
    \Drupal::state()
      ->get('ji_quickbooks_payment_method') ? $settings_count++ : NULL;
    \Drupal::state()
      ->get('ji_quickbooks_payment_account') ? $settings_count++ : NULL;
    \Drupal::state()->get('ji_quickbooks_term') ? $settings_count++ : NULL;
    if (empty($settings_count) || $settings_count < 3) {
      if (\Drupal::currentUser()->hasPermission('access quickbooks')) {
        if ($show_message) {
          \Drupal::messenger()
            ->addError(t('JI QuickBooks is missing the one or more settings. Please make adjustments or save before you use it. Visit the <a href="@url">settings page</a>.', ['@url' => url('admin/config/services/ji_quickbooks')]), FALSE);
        }
      }

      $url = Url::fromRoute('ji_quickbooks.form')->toString();
      \Drupal::logger('JI QuickBooks')
        ->error("JI QuickBooks is missing the one or more settings. Please make adjustments or save before you use it. Visit the <a href='$url'>settings page</a>.");

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks if OAuth is installed/enabled on server.
   *
   * $show_message boolean. Note: Will only show message if user has
   * permission "administer site configuration".
   */
  public static function checkForOauth($show_message = FALSE) {
    $oauth = extension_loaded('oauth');
    if (!$oauth) {
      if (\Drupal::currentUser()->hasPermission('access quickbooks')) {
        if ($show_message) {
          $internal_link = t('JI QuickBooks needs OAuth installed on web server.  <a href="@link">See Status Report</a>.', [
            '@link' => Url::fromRoute('system.status')->toString(),
          ]);
          \Drupal::messenger()->addError($internal_link, FALSE);
        }
      }

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the path of a library.
   */
  public static function getLibraryPath($name = 'v3-php-sdk', $base_path = FALSE) {
    $libraries = &drupal_static(__FUNCTION__);

    if (!isset($libraries)) {
      $libraries = self::getLibraries();
    }

    $path = ($base_path ? base_path() : '');
    if (!isset($libraries[$name])) {
      return FALSE;
    }
    else {
      $path .= $libraries[$name];
    }

    return $path;
  }

  /**
   * Turns human readable string into a machine name.
   */
  public static function getMachineName($human_readable) {
    $machine_readable = strtolower($human_readable);
    return preg_replace('@[^a-z0-9_]+@', '_', $machine_readable);
  }

  /**
   * Retrieve QBO realm id based on UID.
   *
   * If user has synced to a realm before, retrieve the ID of the
   * process to which they synced to.
   *
   * @param int $realm_id
   *   From $quickbooks_service->realmId.
   * @param int $uid
   *   From $order->uid.
   * @param string $process
   *   Use 'customer', 'invoice', 'payment', 'void invoice', or
   *   'void payment' (optional).
   * @param int $oid
   *   From $order. The order id (optional).
   *
   * @return string
   *   QBO ID if found otherwise an empty string.
   */
  public static function getResponseId($realm_id, $uid, $process = 'customer', $oid = NULL) {

    $realm_record = \Drupal::database()
      ->select('ji_quickbooks_realm_index', 'r');

    if (!is_null($oid)) {
      $realm_record->condition('r.oid', $oid, '=');
    }

    $realm_record->condition('r.realm_id', $realm_id, '=')
      ->condition('r.process', $process, '=')
      ->condition('r.uid', $uid, '=')
      ->condition('r.response_id', 0, '>')
      ->fields('r', ['response_id'])
      ->orderBy('updated', 'DESC')
      ->range(0, 1);

    $result = $realm_record->execute()->fetchAll();

    if (isset($result[0]->response_id)) {
      // Don't return zero since we need an empty string instead.
      if ($result[0]->response_id > 0) {
        return $result[0]->response_id;
      }
    }

    // Must return an empty string as required by QBO API.
    return '';
  }

  /**
   * Saves QBO communication info to DB.
   */
  public static function logProcess($oid, $realm_id, $uid, $process, $response) {
    $checker = self::errorChecker($response);

    self::logRealmData($oid, $realm_id, $uid, $process, $checker);

    if ($checker['error']) {
      // Don't execute the next process.
      return FALSE;
    }
    else {
      return $checker['response_id'];
    }
  }

  /**
   * Check for errors.
   *
   * @param array $response
   *   Parsed from the QBO XML response.
   *
   * @return array
   *   'error' => TRUE|FALSE, 'message' => message|response_id
   */
  private static function errorChecker(array $response) {
    if (isset($response['error']['code'])) {
      $error_code_message = 'QBO error: ' . $response['error']['code'] . ' : ' . $response['error']['message'];

      // We don't execute the next process.
      return [
        'error' => 1,
        'message' => $error_code_message,
        'response_id' => 0,
      ];
    }
    else {
      // Passes the response id.
      // Depending on which process this response is from this could be
      // a customer id, invoice id, or a payment id.
      return [
        'error' => 0,
        'message' => '',
        'response_id' => $response['response']->Id,
      ];
    }
  }

  /**
   * Write/remove error messages.
   */
  private static function logRealmData($oid, $realm_id, $uid, $process, $checker, $command = 'insert') {
    switch ($command) {
      case 'insert':
        $table = new \stdClass();
        $table->oid = $oid;
        $table->realm_id = $realm_id;
        $table->process = $process;
        $table->message = $checker['message'];
        $table->is_error = $checker['error'];
        $table->response_id = $checker['response_id'];
        $table->updated = time();
        $table->uid = $uid;

        self::writeDatabaseRecord($table);
        break;

      case 'delete':
        \Drupal::database()->delete('ji_quickbooks_realm_index')
          ->condition('oid', $oid)
          ->condition('realm_id', $realm_id)
          ->condition('process', $process)
          ->execute();
        break;
    }
  }

  /**
   * Write to the database.
   *
   * @param object $table_object
   *   The object that represents the table with
   *   data you wish to insert/update.
   * @param string $table_name
   *   Name of data you wish to access.
   */
  private static function writeDatabaseRecord($table_object, $table_name = 'ji_quickbooks_realm_index') {

    $realm_record = \Drupal::database()
      ->select('ji_quickbooks_realm_index', 'r')
      ->condition('r.oid', $table_object->oid)
      ->condition('r.realm_id', $table_object->realm_id)
      ->condition('r.process', $table_object->process)
      ->fields('r', ['id'])
      ->range(0, 1)
      ->execute()
      ->fetchAll();

    // If not set, then insert, else update.
    if (!isset($realm_record[0]->id)) {
      // Empty id means it will insert.
      \Drupal::database()->insert($table_name)
        ->fields([
          'oid' => $table_object->oid,
          'realm_id' => $table_object->realm_id,
          'process' => $table_object->process,
          'message' => $table_object->message,
          'is_error' => $table_object->is_error,
          'response_id' => $table_object->response_id,
          'updated' => $table_object->updated,
          'uid' => $table_object->uid,
        ])
        ->execute();
    }
    else {
      // 'id' was found, update record.
      $table_object->id = $realm_record[0]->id;

      $query = \Drupal::database()->merge($table_name)
        ->key(['id' => $table_object->id])
        ->fields([
          'oid' => $table_object->oid,
          'realm_id' => $table_object->realm_id,
          'process' => $table_object->process,
          'message' => $table_object->message,
          'is_error' => $table_object->is_error,
          'updated' => $table_object->updated,
          'uid' => $table_object->uid,
        ]);
      // We don't want to lose our ID once it's been set.
      // We return 0 if there was an error.
      if ($table_object->response_id != 0) {
        $query->fields([
          'response_id' => $table_object->response_id,
        ]);
      }
      $query->execute();
    }
  }

  /**
   * Loads all libraries.
   */
  private static function getLibraries() {
    $searchdir[] = 'libraries';
    $searchdir[] = 'vendor/joshideas';
    $searchdir[] = 'vendor/quickbooks';

    // Retrieve list of directories.
    $directories = [];
    $nomask = ['CVS'];
    foreach ($searchdir as $dir) {
      if (is_dir($dir) && $handle = opendir($dir)) {
        while (FALSE !== ($file = readdir($handle))) {
          if (!in_array($file, $nomask) && $file[0] != '.') {
            if (is_dir("$dir/$file")) {
              $directories[$file] = "$dir/$file";
            }
          }
        }
        closedir($handle);
      }
    }

    return $directories;
  }

}
