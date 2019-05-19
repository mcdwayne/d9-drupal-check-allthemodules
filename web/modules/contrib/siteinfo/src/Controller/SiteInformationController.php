<?php

namespace Drupal\siteinfo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\NodeType;

/**
 * Controller for Site Information.
 */
class SiteInformationController extends ControllerBase {

  /**
   * Implement siteInformation function.
   */
  public static function siteInformation() {
    global $databases;
    $content = [];
    $db_name = $databases['default']['default']['database'];
    $db_driver = $databases['default']['default']['driver'];
    $php_version = phpversion();
    $roles = user_roles(TRUE);
    $count_role = count($roles);
    // Get list of config variable.
    $site_name = \Drupal::config('system.site')->get('name');
    $default_theme = \Drupal::config('system.theme')->get('default');
    $admin_theme = \Drupal::config('system.theme')->get('admin');
    $front_page = \Drupal::config('system.site')->get('page.front');
    $country = \Drupal::config('system.date')->get('country.default');
    $time_zone = \Drupal::config('system.date')->get('timezone.default');
    $language_code = \Drupal::config('system.site')->get('default_langcode');
    $file_system = \Drupal::config('system.file')->get('default_scheme');
    $file_path = \Drupal::config('system.file')->get('path.temporary');
    $cron_last = \Drupal::state()->get('system.cron_last');
    $cron_run = \Drupal::service('date.formatter')->formatTimeDiffSince($cron_last);
    $db_driver = \Drupal::database()->driver();
    $db_connection = Database::getConnectionInfo();
    $db_name = $db_connection['default']['database'];

    // Get list of enabled module.
    $query = \Drupal::database()->select('users_field_data', 'u');
    $query->fields('u', ['name']);
    $query->condition('status', 1, '=');
    // Filter by active user.
    $user_name = $query->execute()->fetchAllKeyed(0, 0);
    $count_user = count($user_name);
    // Get list of content type.
    $content_type = NodeType::loadMultiple();
    // Get list of enabled modules.
    $count_content_type = count($content_type);
    $module_list = \Drupal::moduleHandler()->getModuleList();
    $count_module = count($module_list);
    $drupal_version = \Drupal::VERSION;
    // Set header.
    $header = [['data' => "Site Details", 'colspan' => 2]];
    // Set rows.
    $rows['site_name'] = [t("Site Name"), $site_name];
    $rows['drupal_version'] = [t("Drupal Version"), $drupal_version];
    $rows['language_code'] = [t("Default Language Code"), $language_code];
    $rows['country'] = [t("Country"), $country];
    $rows['time_zone'] = [t("Time Zone"), $time_zone];
    $rows['front_page'] = [t("Front Page"), $front_page];
    $rows['cron_run'] = [t("Last Cron Run"), $cron_run];
    $rows['file_system'] = [t("File System"), $file_system];
    $rows['file_path'] = [t("File Path"), $file_path];
    $rows['php_version'] = [t("PHP Version"), $php_version];
    $rows['db_driver'] = [t("Database Driver"), $db_driver];
    $rows['db_name'] = [t("Database Name"), $db_name];
    $rows['default_theme'] = [t("Default Theme"), $default_theme];
    $rows['admin_theme'] = [t("Admin Theme"), $admin_theme];
    $rows['count_role'] = [t("Roles"), $count_role];
    $rows['count_user'] = [t("Active Users"), $count_user];
    $rows['count_content_type'] = [t("Content Types"), $count_content_type];
    $rows['count_module'] = [t("Enabled Modules"), $count_module];
    // Display in table format.
    $content['table_detail'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No entries available.'),
    ];

    $limit = 0;
    // Iteration for content-type.
    foreach ($content_type as $key => $value) {
      $name = $value->get('name');
      $row_col[$limit][0] = t("@cont_name", ['@cont_name' => $name]);
      $query = \Drupal::entityQuery('node')->condition('type', $key)->condition('status', 1)->count();
      $result = $query->execute();
      $row_col[$limit][1] = t("@cout_node", ['@cout_node' => $result]);
      $limit++;
    }
    $limit = 0;
    // Iteration for roles.
    foreach ($roles as $key => $value) {
      $role_name = $value->get('label');
      if (!isset($row_col[$limit][0])) {
        $row_col[$limit][0] = NULL;
      }
      if (!isset($row_col[$limit][1])) {
        $row_col[$limit][1] = NULL;
      }
      // Count number of user for specific role.
      $select = \Drupal::database()->select('user__roles', 'usr');
      $select->fields('usr', ['entity_id']);
      $select->condition('roles_target_id', $key);
      $result = $select->execute()->fetchAllKeyed(0, 0);
      $number_of_usr = count($result);
      // Store in rows.
      $row_col[$limit][2] = t("@role_name", ['@role_name' => $role_name]);
      $row_col[$limit][3] = t("@usr_count", ['@usr_count' => $number_of_usr]);
      $limit++;
    }
    $limit = 0;
    // Iteration for modules.
    foreach ($module_list as $key => $value) {
      if (!isset($row_col[$limit][0])) {
        $row_col[$limit][0] = NULL;
      }
      if (!isset($row_col[$limit][1])) {
        $row_col[$limit][1] = NULL;
      }
      if (!isset($row_col[$limit][2])) {
        $row_col[$limit][2] = NULL;
      }
      if (!isset($row_col[$limit][3])) {
        $row_col[$limit][3] = NULL;
      }
      $row_col[$limit][4] = t("@mod_name", ['@mod_name' => $key]);
      $limit++;
    }
    $limit = 0;
    foreach ($row_col as $key => $value) {
      if (!isset($row_col[$limit][4])) {
        $row_col[$limit][4] = NULL;
      }
      $limit++;
    }
    $header = [
      t('Content Types'), t('Nodes'), t('Roles'), t('Users'), t('Modules'),
    ];
    $content['table_brief'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $row_col,
      '#empty' => t('No entries available.'),
    ];
    return $content;
  }

}
