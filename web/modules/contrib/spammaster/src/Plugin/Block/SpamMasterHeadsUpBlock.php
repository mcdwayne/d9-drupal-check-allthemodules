<?php

namespace Drupal\spammaster\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides 'Firewall Status' Block.
 *
 * @Block(
 *   id = "heads_up",
 *   admin_label = @Translation("Spam Master Heads Up"),
 * )
 */
class SpamMasterHeadsUpBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get Firewall data.
    $query = \Drupal::database()->select('spammaster_threats', 'u');
    $query->fields('u', ['id', 'date', 'threat']);
    // Pagination, we need to extend pagerselectextender and limit the query.
    $query->orderBy('id', 'DESC');
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(100);
    $spammaster_spam_buffer = $pager->execute()->fetchAll();

    $output_d = [];
    $output_t = [];
    foreach ($spammaster_spam_buffer as $results) {
      if (filter_var($results->threat, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $output_d[] = date('Y-m-d', strtotime($results->date));
        $output_t[] = $results->threat;
      }
    }

    // Images url.
    $image_path = \Drupal::request()->getSchemeAndHttpHost() . '/modules/spammaster/images/';
    $image_check = $image_path . 'check-safe.png';
    $image_pass = $image_path . 'check-pass.png';
    $image_lock = 'check-lock.png';
    $image_inactive = 'check-inactive.png';
    // Get module settings.
    $spammaster_settings = \Drupal::config('spammaster.settings');
    $protection_engine_version = constant('SPAMMASTER_VERSION');
    $protection_license_protection = number_format($spammaster_settings->get('spammaster.license_protection'));
    // Check for SSL.
    if (isset($_SERVER["HTTPS"])) {
      $spam_ssl_text = 'Secure Encrypted Website';
      $spam_ssl_image = $image_path . $image_lock;
    }
    else {
      $spam_ssl_text = 'SSL No Encryption';
      $spam_ssl_image = $image_path . $image_inactive;
    }

    return [
      '#theme' => 'heads_up',
      '#type' => 'block',
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
      '#spammaster_table_head' => $this->t('Safe Website'),
      '#image_check' => $image_check,
      '#image_pass' => $image_pass,
      '#protection_engine_version_text' => $this->t('Protection Engine:'),
      '#protection_engine_version' => $protection_engine_version,
      '#protection_license_protection_text' => $this->t('Protected:'),
      '#protection_license_protection' => $protection_license_protection,
      '#protection_license_protection_end' => $this->t('Threats'),
      '#protection_scan_text' => $this->t('Active Scan'),
      '#protection_firewall_text' => $this->t('Active Firewall'),
      '#spam_ssl_image' => $spam_ssl_image,
      '#spam_ssl_text' => $spam_ssl_text,
      '#spammaster_table_footer' => $this->t('<a href="@spammaster_url">Protected by Spam Master</a>', ['@spammaster_url' => 'https://spammaster.techgasp.com/']),
    ];

  }

}
