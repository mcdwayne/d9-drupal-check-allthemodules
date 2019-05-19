<?php

namespace Drupal\spammaster\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class controller.
 */
class SpamMasterLicController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function spammasterlicmanualcreation() {

    // Get variables.
    $site_settings = \Drupal::config('system.site');
    $spammaster_site_name = $site_settings->get('name');
    $spammaster_settings = \Drupal::config('spammaster.settings');
    $spammaster_license = $spammaster_settings->get('spammaster.license_key');
    // Colect data.
    $spammaster_platform = 'Drupal';
    $spammaster_platform_version = \Drupal::VERSION;
    $spammaster_platform_type = 'NO';
    $spammaster_n_websites = '0';
    $spammaster_multisite_joined = $spammaster_platform_type . ' - ' . $spammaster_n_websites;
    $spammaster_version = constant('SPAMMASTER_VERSION');
    $spammaster_type = $spammaster_settings->get('spammaster.type');
    $spammaster_cron = "MAN";
    $spammaster_site_name = $site_settings->get('name');
    $spammaster_site_url = \Drupal::request()->getHost();
    $address_unclean = $spammaster_site_url;
    $address = preg_replace('#^https?://#', '', $address_unclean);
    $spammaster_admin_email = $site_settings->get('mail');
    $spammaster_ip = $_SERVER['SERVER_ADDR'];
    // If empty ip.
    if (empty($spammaster_ip) || $spammaster_ip == '0') {
      $spammaster_ip = 'I ' . gethostbyname($_SERVER['SERVER_NAME']);
    }
    $spammaster_hostname = gethostbyaddr($_SERVER['SERVER_ADDR']);
    // If empty host.
    if (empty($spammaster_hostname) || $spammaster_hostname == '0') {
      $spammaster_hostname = 'H ' . gethostbyname($_SERVER['SERVER_NAME']);
    }

    // Encode ssl post link security.
    $spammaster_license_url = 'aHR0cHM6Ly9zcGFtbWFzdGVyLnRlY2hnYXNwLmNvbS93cC1jb250ZW50L3BsdWdpbnMvc3BhbS1tYXN0ZXItYWRtaW5pc3RyYXRvci9pbmNsdWRlcy9saWNlbnNlL2dldF9saWMucGhw';

    // Call drupal hhtpclient.
    $client = \Drupal::httpClient();
    // Post data.
    $request = $client->post(base64_decode($spammaster_license_url), [
      'form_params' => [
        'spam_license_key' => $spammaster_license,
        'platform' => $spammaster_platform,
        'platform_version' => $spammaster_platform_version,
        'platform_type' => $spammaster_multisite_joined,
        'spam_master_version' => $spammaster_version,
        'spam_master_type' => $spammaster_n_websites,
        'blog_name' => $spammaster_site_name,
        'blog_address' => $address,
        'blog_email' => $spammaster_admin_email,
        'blog_hostname' => $spammaster_hostname,
        'blog_ip' => $spammaster_ip,
        'spam_master_cron' => $spammaster_cron,
      ],
    ]);
    // Decode json data.
    $response = json_decode($request->getBody(), TRUE);
    if (empty($response)) {
      $spammaster_type_set = 'EMPTY';
      $spammaster_status = 'INACTIVE';
      $spammaster_protection_total_number = '0';
      $spammaster_alert_level_received = '';
      $spammaster_alert_level_p_text = '';
    }
    else {
      $spammaster_status = $response['status'];
      if ($spammaster_status == 'MALFUNCTION_3') {
        $spammaster_type_set = 'MALFUNCTION_3';
        $spammaster_protection_total_number = 'MALFUNCTION_3';
        $spammaster_alert_level_received = 'MALFUNCTION_3';
        $spammaster_alert_level_p_text = 'MALFUNCTION_3';
      }
      else {
        $spammaster_type_set = $response['type'];
        $spammaster_protection_total_number = $response['threats'];
        $spammaster_alert_level_received = $response['alert'];
        $spammaster_alert_level_p_text = $response['percent'];
      }
    }
    // Store received data in module settings.
    $config = \Drupal::configFactory()->getEditable('spammaster.settings')
      ->set('spammaster.license_key', $spammaster_license)
      ->set('spammaster.type', $spammaster_type_set)
      ->set('spammaster.license_status', $spammaster_status)
      ->set('spammaster.license_alert_level', $spammaster_alert_level_received)
      ->set('spammaster.license_protection', $spammaster_protection_total_number)
      ->set('spammaster.license_probability', $spammaster_alert_level_p_text)
      ->save();

    // Display status to user.
    if ($spammaster_status == 'INACTIVE' || $spammaster_status == 'MALFUNCTION_1' || $spammaster_status == 'MALFUNCTION_2' || $spammaster_status == 'MALFUNCTION_3' || $spammaster_status == 'EXPIRED') {
      drupal_set_message('License key ' . $spammaster_license . ' status is: ' . $spammaster_status . '. Check Spam Master configuration page and read more about statuses.', 'error');
      // Log message.
      \Drupal::logger('spammaster-license')->notice('Spam Master: license manual status check: ' . $spammaster_status);
      // Spam Master log.
      $spammaster_date = date('Y-m-d H:i:s');
      $spammaster_db_mail_insert = db_insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-license',
        'spamvalue' => 'Spam Master: license manual status check: ' . $spammaster_status,
      ])->execute();
    }
    else {
      // Log message.
      \Drupal::logger('spammaster-license')->notice('Spam Master: license manual status check: ' . $spammaster_status);
      // Spam Master log.
      $spammaster_date = date('Y-m-d H:i:s');
      $spammaster_db_mail_insert = db_insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-license',
        'spamvalue' => 'Spam Master: license manual status check: ' . $spammaster_status,
      ])->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spammasterlicdaily() {

    // Get variables.
    $site_settings = \Drupal::config('system.site');
    $spammaster_site_name = $site_settings->get('name');
    $spammaster_settings = \Drupal::config('spammaster.settings');
    $spammaster_license = $spammaster_settings->get('spammaster.license_key');
    $spammaster_license_status = $spammaster_settings->get('spammaster.license_status');
    $spammaster_license_alert_level = $spammaster_settings->get('spammaster.license_alert_level');
    if ($spammaster_license_status == 'VALID' || $spammaster_license_status == 'MALFUNCTION_1' || $spammaster_license_status == 'MALFUNCTION_2') {
      // Colect data.
      $spammaster_platform = 'Drupal';
      $spammaster_platform_version = \Drupal::VERSION;
      $spammaster_platform_type = 'NO';
      $spammaster_n_websites = '0';
      $spammaster_multisite_joined = $spammaster_platform_type . ' - ' . $spammaster_n_websites;
      $spammaster_version = constant('SPAMMASTER_VERSION');
      $spammaster_type = $spammaster_settings->get('spammaster.type');
      $spammaster_cron = "TRUE";
      $spammaster_site_name = $site_settings->get('name');
      $spammaster_site_url = \Drupal::request()->getHost();
      $address_unclean = $spammaster_site_url;
      $address = preg_replace('#^https?://#', '', $address_unclean);
      $spammaster_admin_email = $site_settings->get('mail');
      $spammaster_ip = $_SERVER['SERVER_ADDR'];
      // If empty ip.
      if (empty($spammaster_ip) || $spammaster_ip == '0') {
        $spammaster_ip = 'I ' . gethostbyname($_SERVER['SERVER_NAME']);
      }
      $spammaster_hostname = gethostbyaddr($_SERVER['SERVER_ADDR']);
      // If empty host.
      if (empty($spammaster_hostname) || $spammaster_hostname == '0') {
        $spammaster_hostname = 'H ' . gethostbyname($_SERVER['SERVER_NAME']);
      }

      // Encode ssl post link security.
      $spammaster_license_url = 'aHR0cHM6Ly9zcGFtbWFzdGVyLnRlY2hnYXNwLmNvbS93cC1jb250ZW50L3BsdWdpbnMvc3BhbS1tYXN0ZXItYWRtaW5pc3RyYXRvci9pbmNsdWRlcy9saWNlbnNlL2dldF9saWMucGhw';

      // Call drupal hhtpclient.
      $client = \Drupal::httpClient();
      // Post data.
      $request = $client->post(base64_decode($spammaster_license_url), [
        'form_params' => [
          'spam_license_key' => $spammaster_license,
          'platform' => $spammaster_platform,
          'platform_version' => $spammaster_platform_version,
          'platform_type' => $spammaster_multisite_joined,
          'spam_master_version' => $spammaster_version,
          'spam_master_type' => $spammaster_n_websites,
          'blog_name' => $spammaster_site_name,
          'blog_address' => $address,
          'blog_email' => $spammaster_admin_email,
          'blog_hostname' => $spammaster_hostname,
          'blog_ip' => $spammaster_ip,
          'spam_master_cron' => $spammaster_cron,
        ],
      ]);
      // Decode json data.
      $response = json_decode($request->getBody(), TRUE);
      if (empty($response)) {
        $spammaster_type_set = 'EMPTY';
        $spammaster_status = 'INACTIVE';
        $spammaster_protection_total_number = '0';
        $spammaster_alert_level_received = '';
        $spammaster_alert_level_p_text = '';
      }
      else {
        $spammaster_status = $response['status'];
        if ($spammaster_status == 'MALFUNCTION_3') {
          $spammaster_type_set = 'MALFUNCTION_3';
          $spammaster_protection_total_number = 'MALFUNCTION_3';
          $spammaster_alert_level_received = 'MALFUNCTION_3';
          $spammaster_alert_level_p_text = 'MALFUNCTION_3';
        }
        else {
          $spammaster_type_set = $response['type'];
          $spammaster_protection_total_number = $response['threats'];
          $spammaster_alert_level_received = $response['alert'];
          $spammaster_alert_level_p_text = $response['percent'];
        }
      }
      // Store received data in module settings.
      $config = \Drupal::configFactory()->getEditable('spammaster.settings')
        ->set('spammaster.license_key', $spammaster_license)
        ->set('spammaster.type', $spammaster_type_set)
        ->set('spammaster.license_status', $spammaster_status)
        ->set('spammaster.license_alert_level', $spammaster_alert_level_received)
        ->set('spammaster.license_protection', $spammaster_protection_total_number)
        ->set('spammaster.license_probability', $spammaster_alert_level_p_text)
        ->save();

      // Call Mail Controller for all requests.
      $spammaster_mail_controller = new SpamMasterMailController();

      // Display status to user.
      if ($spammaster_status == 'INACTIVE' || $spammaster_status == 'MALFUNCTION_1' || $spammaster_status == 'MALFUNCTION_2' || $spammaster_status == 'MALFUNCTION_3') {
        // Log Status.
        \Drupal::logger('spammaster-cron')->notice('Spam Master: cron license warning. Status: ' . $spammaster_status);
        // Spam Master log.
        $spammaster_date = date('Y-m-d H:i:s');
        $spammaster_db_mail_insert = db_insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: cron license warning. Status: ' . $spammaster_status,
        ])->execute();

        // Call Mail Controller function.
        $spammaster_lic_malfunction = $spammaster_mail_controller->spammasterlicmalfunctions();
      }
      if ($spammaster_status == 'EXPIRED') {
        // Log Status.
        \Drupal::logger('spammaster-cron')->notice('Spam Master: cron license warning. Status: ' . $spammaster_status);
        // Spam Master log.
        $spammaster_date = date('Y-m-d H:i:s');
        $spammaster_db_mail_insert = db_insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: cron license warning. Status: ' . $spammaster_status,
        ])->execute();

        // Call Mail Controller function.
        $spammaster_lic_expired = $spammaster_mail_controller->spammasterlicexpired();
      }
      if ($spammaster_status == 'VALID') {
        // Log Status.
        \Drupal::logger('spammaster-cron')->notice('Spam Master: cron license success. Status: ' . $spammaster_status);
        // Spam Master log.
        $spammaster_date = date('Y-m-d H:i:s');
        $spammaster_db_mail_insert = db_insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: cron license success. Status: ' . $spammaster_status,
        ])->execute();
      }
      if ($spammaster_license_alert_level == 'ALERT_3') {
        // Log alert level 3.
        \Drupal::logger('spammaster-cron')->notice('Spam Master: cron alert level 3 detected.');
        // Spam Master log.
        $spammaster_date = date('Y-m-d H:i:s');
        $spammaster_db_mail_insert = db_insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: cron alert level 3 detected.',
        ])->execute();

        // Call Mail Controller function.
        $spammaster_lic_alert_level_3 = $spammaster_mail_controller->spammasterlicalertlevel3();
      }
    }
  }

}
