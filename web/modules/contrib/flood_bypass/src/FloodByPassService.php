<?php

namespace Drupal\flood_bypass;

use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class FloodByPassService.
 */
class FloodByPassService implements FloodByPassServiceInterface {
  /**
   * Drupal\Core\Flood\FloodInterface definition.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Constructs a new FloodByPassService object.
   */
  public function __construct(FloodInterface $flood,
                              ConfigFactoryInterface $configFactory,
                              Connection $database) {
    $this->flood         = $flood;
    $this->configFactory = $configFactory;
    $this->database      = $database;
  }

  /**
   * Function to bypass flood.
   *
   * @param object $user
   */
  public function byPassFlood($user) {
    // Check if flood table exists.
    if (db_table_exists('flood')) {
      // Get all results of failed logged in users.
      $results = $this->getUserFlood();
      if ($results) {
        // Loop through the results.
        foreach ($results as $result) {
          // Explode the individual result.
          $parts       = explode('-', $result->identifier);
          // Get flood user uid.
          $result->uid = $parts[0];
          // Get ip.
          $result->ip  = $parts[1];
          // Check if entered user and flood user are same.
          if ($user->id() == $result->uid) {
            // Check if user has reached the limit.
            $blocked = !$this->flood->isAllowed('user.failed_login_user',
                    $this->configFactory->get('user.flood')->get('user_limit'),
                    $this->configFactory->get('user.flood')->get('user_window'),
                    $result->identifier);
            // If yes.
            if ($blocked) {
              // Try to delete the flood.
              try {
                $userFlood = $this->deleteFailedFloodUserIP('user',
                    $result->identifier);
                $ipFlood = $this->deleteFailedFloodUserIP('ip',
                    $result->ip);
                if ($userFlood and $ipFlood) {
                  drupal_set_message('Maximum limit reached for wrong password',
                      'error', FALSE);
                }
              }
              catch (\Exception $e) {
                // Log the exception to watchdog.
                watchdog_exception('type', $e);
                drupal_set_message('Error: @error', ['@error' => (string) $e],
                    'error');
              }
            }
          }
        }
      }
    }
  }

  /**
   * Deletes the user idetifier entry.
   *
   * @param type $type
   *   Event type.
   * @param type $identifier
   *   User Identifier.
   *
   * @return boolean
   */
  public function deleteFailedFloodUserIP($type, $identifier) {
    // Try to clear the user flood.
    try {
      $success = $this->flood->clear('user.failed_login_' . $type, $identifier);
      if ($success) {
        return TRUE;
      }
    }
    catch (\Exception $e) {
      // Log the exception to watchdog.
      watchdog_exception('type', $e);
      drupal_set_message('Error: @error', ['@error' => (string) $e], 'error');
    }
  }

  /**
   * Returns failed users entry with ip.
   *
   * @return boolean
   */
  public function getUserFlood() {
    // Query database to get failed login user events with ip.
    $query                  = $this->database->select('flood', 'f');
    $query->addField('f', 'identifier');
    $query->addExpression('count(*)', 'count');
    $query->condition('f.event', '%failed_login_user', 'LIKE');
    $query->groupBy('identifier');
    $results                = $query->execute();
    $results->allowRowCount = TRUE;
    if ($results->rowCount() > 0) {
      return $results;
    }
    return FALSE;
  }

}