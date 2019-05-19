<?php

namespace Drupal\spammaster\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class controller.
 */
class SpamMasterCronController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function spammasterdailycron() {
    $spammaster_date = date('Y-m-d H:i:s');
    $spammaster_settings = \Drupal::config('spammaster.settings');
    $spammaster_response_key = $spammaster_settings->get('spammaster.license_status');
    $spammaster_alert_3 = $spammaster_settings->get('spammaster.license_alert_level');
    $spammaster_settings_protection = \Drupal::config('spammaster.settings_protection');
    $spammaster_email_alert_3 = $spammaster_settings_protection->get('spammaster.email_alert_3');
    $spammaster_email_daily_report = $spammaster_settings_protection->get('spammaster.email_daily_report');

    if ($spammaster_response_key == 'VALID' || $spammaster_response_key == 'MALFUNCTION_1' || $spammaster_response_key == 'MALFUNCTION_2') {
      // Implements daily cron request via controllers.
      // Call Lic Controller.
      $spammaster_lic_controller = new SpamMasterLicController();
      $spammaster_lic_daily = $spammaster_lic_controller->spammasterlicdaily();

      // Call Mail Controller.
      $spammaster_mail_controller = new SpamMasterMailController();
      if ($spammaster_email_alert_3 != 0 && $spammaster_alert_3 == 'ALERT_3') {
        $spammaster_mail_daily_alert_3 = $spammaster_mail_controller->spammasterlicalertlevel3();
      }
      if ($spammaster_email_daily_report != 0) {
        $spammaster_mail_daily_report = $spammaster_mail_controller->spammastermaildailyreport();
      }
    }
    else {
      // Log message.
      \Drupal::logger('spammaster-cron')->notice('Spam Master: Warning! daily cron did run, check your license status.');
      $spammaster_db_cron_insert = db_insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-cron',
        'spamvalue' => 'Spam Master: Warning! daily cron did not run, check your license status.',
      ])->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spammasterweeklycron() {
    $spammaster_date = date('Y-m-d H:i:s');
    $spammaster_settings = \Drupal::config('spammaster.settings');
    $response_key = $spammaster_settings->get('spammaster.license_status');
    $spammaster_settings_protection = \Drupal::config('spammaster.settings_protection');
    $spammaster_email_weekly_report = $spammaster_settings_protection->get('spammaster.email_weekly_report');
    $spammaster_email_improve = $spammaster_settings_protection->get('spammaster.email_improve');

    if ($response_key == 'VALID' || $response_key == 'MALFUNCTION_1' || $response_key == 'MALFUNCTION_2') {
      // Implements daily cron request via controllers.
      // Call Mail Controller.
      $spammaster_mail_controller = new SpamMasterMailController();
      if ($spammaster_email_weekly_report != 0) {
        $spammaster_mail_weekly_report = $spammaster_mail_controller->spammastermailweeklyreport();
      }
      if ($spammaster_email_improve != 0) {
        $spammaster_mail_help_report = $spammaster_mail_controller->spammastermailhelpreport();
      }
      $spammaster_cleanup_controller = new SpamMasterCleanUpController();
      $spammaster_cleanup_keys = $spammaster_cleanup_controller->spammastercleanupkeys();
      $spammaster_cleanup_buffer = $spammaster_cleanup_controller->spammastercleanupbuffer();
    }
    else {
      // Log message.
      \Drupal::logger('spammaster-cron')->notice('Spam Master: Warning! weekly cron did run, check your license status.');
      $spammaster_db_cron_insert = db_insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-cron',
        'spamvalue' => 'Spam Master: Warning! weekly cron did not run, check your license status.',
      ])->execute();
    }
  }

}
