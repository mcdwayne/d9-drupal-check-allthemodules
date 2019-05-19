<?php
/**
 * @file
 * Contains \Drupal\smart_ip\SmartIpEvents.
 */

namespace Drupal\smart_ip;

/**
 * Contains all events thrown in the Smart IP query and admin settings.
 *
 * @package Drupal\smart_ip
 */
final class SmartIpEvents {

  /**
   * The QUERY_IP event fired when querying for geolocation based on IP.
   *
   * This event allows you to perform custom actions whenever a query is
   * about to perform. The Smart IP source info is available as argument.
   *
   * @Event
   *
   * @var string
   */
  const QUERY_IP = 'smart_ip.query_ip_location';

  /**
   * The GET_CONFIG_NAME event occurs when Smart IP admin setiings'
   * getEditableConfigNames() is called.
   *
   * This event allows you to add Smart IP source module's config name.
   *
   * @Event
   *
   * @var string
   */
  const GET_CONFIG_NAME = 'smart_ip.get_editable_config_names';

  /**
   * The DATA_ACQUIRED event occurs when querying for geolocation is finished
   * and data is already acquired.
   *
   * This event allows you to alter the queried geolocation data.
   *
   * @Event
   *
   * @var string
   */
  const DATA_ACQUIRED = 'smart_ip.data_acquired';

  /**
   * The DISPLAY_SETTINGS event occurs when Smart IP admin page settings is
   * displayed.
   *
   * This event allows you to alter the Smart IP admin page settings.
   *
   * @Event
   *
   * @var string
   */
  const DISPLAY_SETTINGS = 'smart_ip.display_admin_settings';

  /**
   * The VALIDATE_SETTINGS event occurs when Smart IP admin page settings is
   * validated.
   *
   * This event allows you to alter the Smart IP admin page settings validation.
   *
   * @Event
   *
   * @var string
   */
  const VALIDATE_SETTINGS = 'smart_ip.validate_admin_settings';

  /**
   * The SUBMIT_SETTINGS event occurs when Smart IP admin page settings is
   * submitted.
   *
   * This event allows you to alter the Smart IP admin page settings submission.
   *
   * @Event
   *
   * @var string
   */
  const SUBMIT_SETTINGS = 'smart_ip.submit_admin_settings';

  /**
   * The CRON_RUN event occurs when Drupal cron runs.
   *
   * This event allows you to act on Drupal cron runs.
   *
   * @Event
   *
   * @var string
   */
  const CRON_RUN = 'smart_ip.cron_run';

  /**
   * The MANUAL_UPDATE event occurs when Smart IP admin page settings is
   * executed manual database update.
   *
   * This event allows you to act on manual database update.
   *
   * @Event
   *
   * @var string
   */
  const MANUAL_UPDATE = 'smart_ip.manual_database_update';

}
