<?php

namespace Drupal\acsf;

use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Manages theme notifications that need to be sent to the Factory,
 */
class AcsfThemeNotify {
  use StringTranslationTrait;

  /**
   * The ACSF variable storage.
   *
   * @var \Drupal\acsf\AcsfVariableStorage
   */
  protected $acsfVarStorage;

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructor.
   *
   * @param \Drupal\acsf\AcsfVariableStorage $variable_storage
   *   The ACSF variable storage service.
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(AcsfVariableStorage $variable_storage, Connection $database, TranslationInterface $string_translation) {
    $this->acsfVarStorage = $variable_storage;
    $this->database = $database;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Sends a theme notification to the Factory.
   *
   * This is going to contact the Factory, so it qualifies as a third-party
   * call, therefore calling it during a normal page load is not advisable. A
   * possibly safer solution could be executing this via a menu callback called
   * through an asynchronous JavaScript call.
   *
   * If the request does not succeed (and $store_failed_notification is truthy),
   * the notification will be stored so that we may try again later when cron
   * runs.
   *
   * @param string $scope
   *   The scope. Either "theme", "site", "group", or "global".
   * @param string $event_type
   *   The type of theme event that occurred. Either "create", "modify", or
   *   "delete".
   * @param int $nid
   *   The node ID associated with the scope. Only required for "group" scope
   *   notifications. If empty, it will be filled in automatically for "theme"
   *   and "site" scope notifications.
   * @param string $theme
   *   The system name of the theme the event relates to. Only relevant for
   *   "theme" scope notifications.
   * @param int $timestamp
   *   The timestamp when the notification was created.
   * @param bool $store_failed_notification
   *   (optional) If TRUE, disable storing a notification when the sending
   *   fails. Should be only used in case of notifications which have been
   *   already added to the pending notification table.
   *
   * @return array
   *   The message response body and code.
   */
  public function sendNotification($scope, $event_type, $nid = NULL, $theme = NULL, $timestamp = NULL, $store_failed_notification = TRUE) {
    if (!$this->isEnabled()) {
      return [
        'code' => 500,
        'data' => ['message' => $this->t('The theme change notification feature is not enabled.')],
      ];
    }

    try {
      if (empty($nid) && in_array($scope, ['theme', 'site'])) {
        $site = AcsfSite::load();
        $nid = $site->site_id;
      }
      $parameters = [
        'scope' => $scope,
        'event' => $event_type,
        'nid' => $nid,
      ];
      if ($theme) {
        $parameters['theme'] = $theme;
      }
      if ($timestamp) {
        $parameters['timestamp'] = $timestamp;
      }
      $message = new AcsfMessageRest('POST', 'site-api/v1/theme/notification', $parameters);
      $message->send();
      $response = [
        'code' => $message->getResponseCode(),
        'data' => $message->getResponseBody(),
      ];
    }
    catch (\Exception $e) {
      $error_message = $this->t('AcsfThemeNotify failed with error: @message.', ['@message' => $e->getMessage()]);
      syslog(LOG_ERR, $error_message);

      // Send a log message to the Factory.
      $acsf_log = new AcsfLog();
      $acsf_log->log('theme_notify', $error_message, LOG_ERR);

      $response = [
        'code' => 500,
        'data' => ['message' => $error_message],
      ];
    }

    if ($store_failed_notification && $response['code'] !== 200) {
      $this->addNotification($event_type, $theme);
    }

    return $response;
  }

  /**
   * Resends failed theme notifications.
   *
   * @param int $limit
   *   The number of notification that should be processed.
   *
   * @return int
   *   Returns the number of successfully sent notifications, or -1 if none of
   *   the pending notifications managed to get sent.
   */
  public function processNotifications($limit) {
    if (!$this->isEnabled()) {
      return -1;
    }

    $notifications = $this->getNotifications($limit);

    // If there were no pending notifications then we can consider this process
    // successful.
    $success = 0;

    foreach ($notifications as $notification) {
      // If this is a notification for an event that is not supported, it will
      // never get a 200 response so we need to remove it from storage.
      if (!in_array($notification->event, ['create', 'modify', 'delete'])) {
        $this->removeNotification($notification);
        continue;
      }

      // Increment the count of attempts on this notification. At the first pass
      // through this function, this notification has already been attempted
      // once.
      $this->incrementNotificationAttempts($notification);

      // Remove notification and handle if it exceeds the maximum allowed
      // attempts (default 3). The assumption behind the >= comparison here is
      // that the notification was already tried once before it was stored in
      // the table.
      if ($notification->attempts >= $this->acsfVarStorage->get('acsf_theme_notification_max_attempts', 3)) {
        $this->removeNotification($notification);
        // @todo Any additional handling needed? DG-11826
      }
      // Only "site" or "theme" notifications get stored. Any notification with
      // a non-empty theme field is assumed to be a theme notification,
      // otherwise it is a site notification.
      $scope = !empty($notification->theme) ? 'theme' : 'site';
      // Try to send the notification but if it fails do not store it again.
      $response = $this->sendNotification($scope, $notification->event, NULL, $notification->theme, $notification->timestamp, FALSE);
      if ($response['code'] === 200) {
        $this->removeNotification($notification);
        $success++;
      }
    }

    return $success == 0 && !empty($notifications) ? -1 : $success;
  }

  /**
   * Indicates whether theme notifications are enabled.
   *
   * If this method returns FALSE, theme notifications will not be sent to the
   * Site Factory.
   *
   * @return bool
   *   TRUE if notifications are enabled; FALSE otherwise.
   */
  public function isEnabled() {
    return $this->acsfVarStorage->get('acsf_theme_enabled', TRUE);
  }

  /**
   * Gets a list of stored notifications to be resent.
   *
   * @param int $limit
   *   The number of notifications to fetch.
   *
   * @return object[]
   *   An array of theme notification objects.
   */
  public function getNotifications($limit) {
    return $this->database->select('acsf_theme_notifications', 'n')
      ->fields('n', ['id', 'event', 'theme', 'timestamp', 'attempts'])
      ->orderBy('timestamp', 'ASC')
      ->range(0, $limit)
      ->execute()
      ->fetchAll();
  }

  /**
   * Stores a theme notification for resending later.
   *
   * If the initial request to send the notification to the Factory fails, we
   * store it and retry later on cron.
   *
   * @param string $event_type
   *   The type of theme event that occurred.
   * @param string $theme
   *   The system name of the theme the event relates to.
   */
  public function addNotification($event_type, $theme) {
    $this->database->insert('acsf_theme_notifications')
      ->fields([
        'timestamp' => time(),
        'event' => $event_type,
        'theme' => $theme,
        'attempts' => 1,
      ])
      ->execute();
  }

  /**
   * Increments the stored number of attempts for a notification.
   *
   * @param object $notification
   *   A notification object, in a format as returned by getNotifications().
   */
  public function incrementNotificationAttempts($notification) {
    $this->database->update('acsf_theme_notifications')
      ->fields([
        'attempts' => ++$notification->attempts,
      ])
      ->condition('id', $notification->id)
      ->execute();
  }

  /**
   * Removes a pending notification from the database.
   *
   * @param object $notification
   *   A notification object, in a format as returned by getNotifications().
   */
  public function removeNotification($notification) {
    $this->database->delete('acsf_theme_notifications')
      ->condition('id', $notification->id)
      ->execute();
  }

}
