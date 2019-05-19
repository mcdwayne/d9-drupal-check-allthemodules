<?php

namespace Drupal\site_notifications;

/**
 * Class SiteNotificationsHelper.
 */
class SiteNotificationsHelper {

  /**
   * Implements getSettings().
   *
   * Get all configurable settings from table.
   */
  public static function getSettings() {
    $result = db_query('SELECT * FROM {site_notifications_settings}')->fetchAllAssoc('id');
    return $result;
  }

  /**
   * Implements truncate().
   *
   * Truncate table for settings.
   */
  public static function truncate() {
    db_query("TRUNCATE TABLE {site_notifications_settings}")->execute();
  }

  /**
   * Save an entry in the database.
   *
   * The underlying insert function is db_insert().
   *
   * Exception handling is shown in this example. It could be simplified
   * without the try/catch blocks, but since an insert will throw an exception
   * and terminate your application if the exception is not handled, it is best
   * to employ try/catch.
   *
   * @param string $table_name
   *   Public static function insert table_name.
   * @param array $fields
   *   Public static function insert fields.
   *
   * @throws \Exception
   *   When the database insert fails.
   *
   * @see db_insert()
   */
  public static function insert($table_name, array $fields) {
    try {
      db_insert("$table_name")
        ->fields($fields)
        ->execute();
    }
    catch (\Exception $e) {
      drupal_set_message(t('db_insert failed. Message = %message, query= %query', [
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      ]
      ), 'error');
    }
  }

  /**
   * Implements stringToArray().
   */
  public static function stringToArray($str) {
    $result = [];
    $result = explode(',', $str);
    return $result;
  }

  /**
   * Implements inArrayAny().
   */
  public static function inArrayAny($needles, $haystack) {
    return !!array_intersect($needles, $haystack);
  }

  /**
   * Implements getNotificationsData().
   */
  public static function getNotificationsData($listing = 0) {
    $block_content['output'] = "";
    $block_content['count']  = 0;
    $output                  = "<div class='notification_block'>";
    $output_inner            = "";
    $output_inner_link       = "";
    $see_all                 = "";
    $notify_status           = 0;
    $user_access             = 0;
    $refresh_interval        = NULL;
    $show_notification_count = 0;
    $notifications           = [];

    $settings = SiteNotificationsHelper::getSettings();

    if (isset($settings[1]) && !empty($settings[1])) {
      if ($settings[1]->notify_status == 1) {

        $show_notification_count = $settings[1]->show_notification_count;
        $content_types_array = [];
        if ($settings[1]->content_types != '') {
          $content_types_array = SiteNotificationsHelper::stringToArray($settings[1]->content_types);
        }
        $roles_array = [];
        if ($settings[1]->roles != '') {
          $roles_array = SiteNotificationsHelper::stringToArray($settings[1]->roles);
        }

        $user_role = \Drupal::currentUser()->getRoles();
        if (SiteNotificationsHelper::inArrayAny($user_role, $roles_array)) {

          // If $user_access = 1, means asynchronous request will be called
          // in background to update notifications provided refresh_interval
          // is set in configurations.
          $user_access = 1;

          $select = db_select('site_notifications', 'sn');
          $select->fields('sn');
          if (count($content_types_array)) {
            $select->condition('type', 'node');
            $select->condition('sub_type', $content_types_array, 'IN');
          }
          else {
            $select->condition('type', 'node', '!=');
          }
          $select->orderBy('created', 'DESC');

          $notifications_count = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

          $select->range(0, $show_notification_count);

          $notifications = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);
        }
      }
    }
    $output .= $output_inner;
    $output .= "</div>";

    if (isset($_POST['request']) && $_POST['request'] == 'ajax' && $user_access == 1) {
      if (count($notifications)) {

        $output_inner .= "<ul style='list-style-type:circle;'>";
        foreach ($notifications as $notification) {
          $output_inner .= "<li>" . $notification['message'] . "</li>";
        }
        $output_inner .= "</ul>";

        if ($show_notification_count != 0 && $show_notification_count < count($notifications_count)) {
          $link    = base_path() . 'notification/list';
          $see_all = t('<a href="@link">See all</a>', ['@link' => $link]);
        }
        $output_inner_link .= "<div>" . $see_all . "</div>";

        $output_inner .= $output_inner_link;
      }
      else {
        $output_inner .= "<div> No notifications available.</div>";
      }
      $return['output_inner'] = $output_inner;
      $return['count']        = count($notifications_count);
      $return['user_access']  = $user_access;

      return $return;
    }
    elseif ($listing == 1 && $user_access == 1) {
      $block_content['count'] = [];

      if (count($notifications_count) == 0) {
        $block_content['output'] = ['message' => "No Notifications Available."];
      }
      else {
        $block_content['output'] = $notifications_count;
        $block_content['count'] = count($notifications_count);
      }
      $block_content['user_access'] = $user_access;

      return $block_content;
    }
    else {
      $block_content['count'] = [];

      if (count($notifications_count) == 0) {
        $block_content['output'] = ['message' => "No Notifications Available."];
      }
      else {
        $block_content['output'] = $notifications;
        $block_content['count'] = count($notifications_count);
      }

      if ($settings[1]->refresh_interval != "" || $settings[1]->refresh_interval != NULL) {
        $refresh_interval = $settings[1]->refresh_interval;
        $refresh_interval = intval($refresh_interval);
      }
      $block_content['refresh_interval'] = $refresh_interval;

      if ($settings[1]->notify_status == '1') {
        $notify_status = $settings[1]->notify_status;
      }
      $block_content['notify_status'] = intval($notify_status);
      $block_content['user_access']   = intval($user_access);

      if (count($notifications_count) && ($show_notification_count != 0 && $show_notification_count < count($notifications_count))) {
        $link    = base_path() . 'notification/list';
        $see_all = t('<a href="@link">See all</a>', ['@link' => $link]);
      }
      $block_content['link'] = $see_all;
      return $block_content;
    }
  }

  /**
   * Implements siteNotificationsDatabaseHolder().
   */
  public static function siteNotificationsDatabaseHolder($node, $message) {
    global $base_url;
    $type     = 'node';
    $sub_type = $node->get('type')->target_id;
    $type_id  = $node->id();
    $from_uid = $node->get('uid')->target_id;
    $changed  = $node->get('changed')->value;
    $user     = user_load($from_uid);
    $name     = $user->get('name')->value;
    $title    = "<a href='" . $base_url . "/node/" . $type_id . "' >" . $node->get('title')->value . "</a>";

    if ($message == " has created ") {
      $message = $name . $message . $title;
      $fields = [
        'type'     => $type,
        'sub_type' => $sub_type,
        'type_id'  => $type_id,
        'from_uid' => $from_uid,
        'message'  => $message,
        'created'  => $changed,
      ];

      SiteNotificationsHelper::insert('site_notifications', $fields);
    }
    else {
      $message = $name . $message . $title;
      $query = \Drupal::database()->update('site_notifications');
      $query->fields([
        'message' => $message,
        'created' => $changed,
      ]);
      $query->condition('type_id', $type_id);
      $query->condition('from_uid', $from_uid);

      $result = $query->execute();

      // If 0 means, content notifications while adding was not present.
      // in the table.
      // So, in this case, insert fresh notification for updated content.
      if ($result == 0) {
        $message = $name . $message . $title;
        $fields = [
          'type'     => $type,
          'sub_type' => $sub_type,
          'type_id'  => $type_id,
          'from_uid' => $from_uid,
          'message'  => $message,
          'created'  => $changed,
        ];

        SiteNotificationsHelper::insert('site_notifications', $fields);
      }

    }
  }

}
